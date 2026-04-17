<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * StockController -- Phase 3 of the toolbox.
 *
 * Endpoints :
 *  - GET  /stocks                       : public page with the global stock table
 *  - GET  /stocks/{slug}                : detail page for a single stock_item
 *  - GET  /stocks/validations           : pending attribution approvals (treasurer+)
 *  - GET  /stocks/import                : CSV/Excel import page (treasurer+)
 *  - GET  /stocks/api/list              : JSON catalog + aggregated stats
 *  - GET  /stocks/api/item/{slug}       : JSON detail + recent movements
 *  - PUT  /stocks/api/item/{slug}/quantity : quantite absolue (+ mouvement adjustment)
 *  - GET  /stocks/api/attributions      : JSON list of attributions (mine or all)
 *  - POST /stocks/api/attribute         : create an attribution movement
 *  - POST /stocks/api/reconcile/{id}    : reconcile an attribution (return/loss/gift)
 *  - GET  /stocks/api/validations       : pending approvals list
 *  - POST /stocks/api/validations/{id}/approve
 *  - POST /stocks/api/validations/{id}/reject
 *  - POST /stocks/api/import/preview    : parse the uploaded CSV and return preview
 *  - POST /stocks/api/import/commit     : apply the parsed import
 */
class StockController extends Controller
{
    private const PAGE_KEY         = 'stocks_generique';
    private const PAGE_VALIDATIONS = 'stocks_validations';
    private const PAGE_IMPORT      = 'stocks_import';

    // ─────────────────────────────────────────────────────────
    // Views
    // ─────────────────────────────────────────────────────────

    public function index()
    {
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('stocks', [
            'members'       => $members,
            'categoriesMap' => StockItem::CATEGORIES,
            'reasonsMap'    => StockMovement::REASONS,
        ]);
    }

    public function show(string $slug)
    {
        $item    = StockItem::where('slug', $slug)->firstOrFail();
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('stocks-detail', [
            'item'          => $item,
            'members'       => $members,
            'categoriesMap' => StockItem::CATEGORIES,
            'reasonsMap'    => StockMovement::REASONS,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Auth helpers
    // ─────────────────────────────────────────────────────────

    private function authUser(Request $request): ?User
    {
        $userId = $request->header('X-Sim-User');

        return $userId ? User::find($userId) : null;
    }

    private function requireAccess(Request $request, string $pageKey = self::PAGE_KEY): ?JsonResponse
    {
        $user = $this->authUser($request);
        if (! $user) {
            return response()->json(['error' => 'unauthorized'], 403);
        }
        if (! $user->canAccessPage($pageKey)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────
    // Public (officer+) API
    // ─────────────────────────────────────────────────────────

    public function apiList(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $items = StockItem::active()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Quantites « sorties du coffre » (hors attributions marquees « hors stock »).
        $attributed = StockMovement::openAttribution()
            ->where(function ($q) {
                $q->where('from_external', false)->orWhereNull('from_external');
            })
            ->select('stock_item_id', DB::raw('SUM(ABS(quantity_change)) as qty'))
            ->groupBy('stock_item_id')
            ->pluck('qty', 'stock_item_id');

        $catalog = $items->map(function (StockItem $i) use ($attributed) {
            $out = (int) ($attributed[$i->id] ?? 0);

            return [
                'id'                 => $i->id,
                'category'           => $i->category,
                'category_label'     => StockItem::CATEGORIES[$i->category] ?? $i->category,
                'slug'               => $i->slug,
                'name'               => $i->name,
                'quantity'           => (int) $i->quantity,
                'out_attributed'     => $out,
                'total_physical'     => (int) $i->quantity + $out,
                'unit_weight_g'      => $i->unit_weight_g,
                'default_sell_price' => $i->default_sell_price,
                'default_purchase_price' => $i->default_purchase_price,
                'is_sellable'        => (bool) $i->is_sellable,
                'notes'              => $i->notes,
            ];
        });

        // Totals per category.
        $totals = $catalog->groupBy('category')->map(function ($rows, $cat) {
            $weightG = $rows->sum(function ($r) {
                return ($r['unit_weight_g'] ?? 0) * $r['quantity'];
            });

            return [
                'category'       => $cat,
                'category_label' => StockItem::CATEGORIES[$cat] ?? $cat,
                'item_count'     => $rows->count(),
                'quantity'       => $rows->sum('quantity'),
                'out_attributed' => $rows->sum('out_attributed'),
                'weight_g'       => (int) $weightG,
            ];
        })->values();

        $maxKg = (int) Setting::get('stock_max_capacity_kg', 1000);
        $currentKg = (int) round($totals->sum('weight_g') / 1000);

        return response()->json([
            'catalog'      => $catalog->values(),
            'totals'       => $totals,
            'categories'   => StockItem::CATEGORIES,
            'capacity_kg'  => $maxKg,
            'current_kg'   => $currentKg,
        ]);
    }

    public function apiItem(Request $request, string $slug): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $item = StockItem::where('slug', $slug)->first();
        if (! $item) {
            return response()->json(['error' => 'Article introuvable'], 404);
        }

        $openAttrModels = StockMovement::openAttribution()
            ->where('stock_item_id', $item->id)
            ->with(['attributedTo:id,name,role', 'user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        $openAttr = $openAttrModels->map(fn (StockMovement $m) => $this->mapMovement($m, withAttribution: true));

        $outAttributedVault = (int) $openAttrModels
            ->filter(fn (StockMovement $m) => ! $m->from_external)
            ->sum(fn (StockMovement $m) => abs((int) $m->quantity_change));

        $movements = StockMovement::where('stock_item_id', $item->id)
            ->with(['user:id,name', 'attributedTo:id,name,role'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn (StockMovement $m) => $this->mapMovement($m));

        $salesTotal = (int) Sale::where('stock_item_id', $item->id)->sum('total_price');
        $salesCount = (int) Sale::where('stock_item_id', $item->id)->count();

        return response()->json([
            'item' => [
                'id'                 => $item->id,
                'category'           => $item->category,
                'category_label'     => StockItem::CATEGORIES[$item->category] ?? $item->category,
                'slug'               => $item->slug,
                'name'               => $item->name,
                'quantity'           => (int) $item->quantity,
                'out_attributed'     => $outAttributedVault,
                'unit_weight_g'      => $item->unit_weight_g,
                'default_sell_price' => $item->default_sell_price,
                'default_purchase_price' => $item->default_purchase_price,
                'is_sellable'        => (bool) $item->is_sellable,
                'notes'              => $item->notes,
            ],
            'open_attributions' => $openAttr,
            'movements'         => $movements,
            'sales_total'       => $salesTotal,
            'sales_count'       => $salesCount,
        ]);
    }

    /**
     * Met a jour les metadonnees d'un stock_item (nom, categorie, prix de vente,
     * prix d'achat, poids, vendabilite, notes). Un mouvement d'ajustement de
     * quantity=0 est cree avec un resume des champs changes pour la tracabilite.
     * Reserve officier+ (scope `stocks_generique`). La quantite ne se modifie
     * PAS ici : utiliser PUT .../quantity, l'import CSV ou Filament.
     */
    public function apiUpdateItem(Request $request, string $slug): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }
        $user = $this->authUser($request);

        $item = StockItem::where('slug', $slug)->first();
        if (! $item) {
            return response()->json(['error' => 'Article introuvable'], 404);
        }

        $categories = array_keys(StockItem::CATEGORIES);

        $v = Validator::make($request->all(), [
            'name'                   => 'sometimes|required|string|max:120',
            'category'               => 'sometimes|required|string|in:' . implode(',', $categories),
            'default_sell_price'     => 'sometimes|nullable|integer|min:0|max:999999999',
            'default_purchase_price' => 'sometimes|nullable|integer|min:0|max:999999999',
            'unit_weight_g'          => 'sometimes|nullable|integer|min:0|max:9999999',
            'is_sellable'            => 'sometimes|boolean',
            'is_active'              => 'sometimes|boolean',
            'notes'                  => 'sometimes|nullable|string|max:1000',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $data = $v->validated();
        $changes = [];

        foreach ($data as $field => $new) {
            $old = $item->{$field};
            $oldNorm = is_bool($old) ? (int) $old : $old;
            $newNorm = is_bool($new) ? (int) $new : $new;
            if ((string) $oldNorm !== (string) $newNorm) {
                $changes[$field] = ['from' => $old, 'to' => $new];
            }
        }

        if (empty($changes)) {
            return response()->json(['ok' => true, 'message' => 'Aucune modification', 'item' => $this->mapItem($item)]);
        }

        DB::transaction(function () use ($item, $data, $changes, $user) {
            $item->update($data);

            $summary = collect($changes)->map(function ($c, $field) {
                $from = is_null($c['from']) || $c['from'] === '' ? 'null' : (string) $c['from'];
                $to   = is_null($c['to'])   || $c['to']   === '' ? 'null' : (string) $c['to'];
                if (mb_strlen($from) > 40) {
                    $from = mb_substr($from, 0, 40) . '...';
                }
                if (mb_strlen($to) > 40) {
                    $to = mb_substr($to, 0, 40) . '...';
                }

                return $field . ': ' . $from . ' -> ' . $to;
            })->implode(' | ');

            StockMovement::create([
                'stock_item_id'   => $item->id,
                'quantity_change' => 0,
                'reason'          => 'adjustment',
                'user_id'         => $user->id,
                'notes'           => 'Modification fiche article : ' . $summary,
                'created_at'      => now(),
            ]);
        });

        return response()->json([
            'ok'      => true,
            'message' => 'Article mis a jour (' . count($changes) . ' champ(s))',
            'item'    => $this->mapItem($item->fresh()),
            'changes' => array_keys($changes),
        ]);
    }

    /**
     * Definit la quantite en stock (valeur absolue) et trace un mouvement `adjustment`.
     */
    public function apiSetQuantity(Request $request, string $slug): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }
        $user = $this->authUser($request);

        $item = StockItem::where('slug', $slug)->first();
        if (! $item) {
            return response()->json(['error' => 'Article introuvable'], 404);
        }

        $v = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:-999999999|max:999999999',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $new = (int) $v->validated()['quantity'];
        $old = (int) $item->quantity;
        $delta = $new - $old;

        if ($delta === 0) {
            return response()->json([
                'ok'      => true,
                'message' => 'Aucun changement',
                'item'    => $this->mapItem($item),
            ]);
        }

        DB::transaction(function () use ($item, $new, $old, $delta, $user) {
            $item->update(['quantity' => $new]);

            StockMovement::create([
                'stock_item_id'   => $item->id,
                'quantity_change' => $delta,
                'reason'          => 'adjustment',
                'user_id'         => $user->id,
                'notes'           => 'Ajustement quantite (interface stocks) : ' . $old . ' -> ' . $new,
                'created_at'      => now(),
            ]);
        });

        return response()->json([
            'ok'      => true,
            'message' => 'Quantite mise a jour (' . $old . ' -> ' . $new . ')',
            'item'    => $this->mapItem($item->fresh()),
        ]);
    }

    /**
     * Mapping d'un stock_item pour les reponses API.
     */
    private function mapItem(StockItem $i): array
    {
        return [
            'id'                     => $i->id,
            'category'               => $i->category,
            'category_label'         => StockItem::CATEGORIES[$i->category] ?? $i->category,
            'slug'                   => $i->slug,
            'name'                   => $i->name,
            'quantity'               => (int) $i->quantity,
            'unit_weight_g'          => $i->unit_weight_g,
            'default_sell_price'     => $i->default_sell_price,
            'default_purchase_price' => $i->default_purchase_price,
            'is_sellable'            => (bool) $i->is_sellable,
            'is_active'              => (bool) $i->is_active,
            'notes'                  => $i->notes,
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Attributions (Phase 3.2 + 3.3)
    // ─────────────────────────────────────────────────────────

    /**
     * List attributions, scope=mine returns only the current user as beneficiary.
     * scope=all (officer+) returns everything.
     * status=open|reconciled|rejected|all, default open.
     */
    public function apiAttributions(Request $request): JsonResponse
    {
        $user = $this->authUser($request);
        if (! $user) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        $scope  = $request->query('scope', 'mine');
        $status = $request->query('status', 'open');

        $q = StockMovement::query()
            ->where('reason', 'attribution')
            ->with([
                'stockItem:id,slug,name,category,default_sell_price',
                'attributedTo:id,name,role',
                'user:id,name',
                'approvedBy:id,name',
            ]);

        if ($scope === 'all') {
            if (! $user->canAccessPage(self::PAGE_KEY)) {
                return response()->json(['error' => 'Accès refusé'], 403);
            }
        } else {
            $q->where('attributed_to_user_id', $user->id);
        }

        switch ($status) {
            case 'open':
                $q->whereNull('reconciled_at')->whereNull('rejected_at');
                break;
            case 'reconciled':
                $q->whereNotNull('reconciled_at');
                break;
            case 'rejected':
                $q->whereNotNull('rejected_at');
                break;
            case 'all':
            default:
                break;
        }

        $rows = $q->orderBy('created_at', 'desc')->limit(200)->get()
            ->map(fn (StockMovement $m) => $this->mapMovement($m, withAttribution: true));

        return response()->json(['attributions' => $rows]);
    }

    public function apiAttribute(Request $request): JsonResponse
    {
        $user = $this->authUser($request);
        if (! $user) {
            return response()->json(['error' => 'unauthorized'], 403);
        }
        if (! $user->canAccessPage(self::PAGE_KEY)) {
            return response()->json(['error' => 'Accès refusé (officier minimum)'], 403);
        }

        $v = Validator::make($request->all(), [
            'stock_item_id'         => 'required|integer|exists:stock_items,id',
            'quantity'              => 'required|integer|min:1|max:999999999',
            'attributed_to_user_id' => 'required|integer|exists:users,id',
            'notes'                 => 'nullable|string|max:500',
            'from_external'         => 'sometimes|boolean',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $item = StockItem::find($request->input('stock_item_id'));
        if (! $item || ! $item->is_active) {
            return response()->json(['error' => 'Article indisponible'], 404);
        }

        $target = User::find($request->input('attributed_to_user_id'));
        if (! $target) {
            return response()->json(['error' => 'Beneficiaire introuvable'], 404);
        }

        $qty = (int) $request->input('quantity');
        $fromExternal = $request->boolean('from_external');

        $threshold = (int) Setting::get('attribution_approval_threshold', 0);
        $unitPrice = (int) ($item->default_sell_price ?? 0);
        $valueTotal = $unitPrice * $qty;
        $requiresApproval = $threshold > 0 && $valueTotal >= $threshold && ! $user->isTreasurer();

        $warning = null;
        if (! $fromExternal && $item->quantity < $qty) {
            $warning = 'Stock insuffisant (' . $item->quantity . ' disponibles). Le stock passera en negatif.';
        }

        $notes = trim((string) $request->input('notes', ''));
        if ($fromExternal) {
            $suffix = '[Hors stock central — trace uniquement]';
            $notes = $notes === '' ? $suffix : $notes . ' ' . $suffix;
        }

        DB::transaction(function () use ($item, $qty, $user, $target, $notes, $requiresApproval, $fromExternal) {
            if (! $fromExternal) {
                $item->decrement('quantity', $qty);
            }
            StockMovement::create([
                'stock_item_id'              => $item->id,
                'quantity_change'            => -$qty,
                'attribution_original_abs'   => $qty,
                'reason'                     => 'attribution',
                'unit_cost'                  => $item->default_purchase_price,
                'user_id'                    => $user->id,
                'attributed_to_user_id'      => $target->id,
                'notes'                      => $notes !== '' ? $notes : null,
                'requires_approval'        => $requiresApproval,
                'from_external'              => $fromExternal,
                'created_at'                 => now(),
            ]);
        });

        return response()->json([
            'ok'      => true,
            'message' => $qty . '× ' . $item->name . ' attribue(s) a ' . $target->name
                . ($fromExternal ? ' (hors stock central)' : '')
                . ($requiresApproval ? ' (en attente de validation tresorier)' : ''),
            'warning' => $warning,
            'requires_approval' => $requiresApproval,
        ]);
    }

    /**
     * Reconcile an attribution. Action is one of:
     *  - return   : items came back to the central stock (adjustment +qty).
     *  - loss     : items are lost/stolen/consumed (no stock change, documented).
     *  - gift     : gift to someone, same as loss.
     *
     * For action=sold, the reconciliation happens via SaleController::apiCreate when
     * the beneficiary posts a sale with ?attribution_id=X (see Phase 3.3 plan).
     */
    public function apiReconcile(Request $request, int $id): JsonResponse
    {
        $user = $this->authUser($request);
        if (! $user) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        $movement = StockMovement::where('id', $id)->where('reason', 'attribution')->first();
        if (! $movement) {
            return response()->json(['error' => 'Attribution introuvable'], 404);
        }
        if ($movement->reconciled_at || $movement->rejected_at) {
            return response()->json(['error' => 'Deja reconciliee ou rejetee'], 422);
        }

        // Only the beneficiary or an officer+ may reconcile.
        $isBeneficiary = $movement->attributed_to_user_id === $user->id;
        $canOverride   = $user->canAccessPage(self::PAGE_KEY);
        if (! $isBeneficiary && ! $canOverride) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $v = Validator::make($request->all(), [
            'action'   => 'required|in:return,loss,gift',
            'notes'    => 'nullable|string|max:500',
            'quantity' => 'nullable|integer|min:1|max:999999999',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $action  = (string) $request->input('action');
        $notes   = $request->input('notes');
        $origAbs = abs((int) $movement->quantity_change);
        $part    = (int) ($request->input('quantity') ?? $origAbs);
        if ($part < 1 || $part > $origAbs) {
            return response()->json(['error' => 'Quantite entre 1 et ' . $origAbs . ' (reste sur cette attribution)'], 422);
        }

        $item = $movement->stockItem;
        if (! $item) {
            return response()->json(['error' => 'Article introuvable'], 404);
        }

        $reason     = 'adjustment';
        $stockDelta = 0;
        $label      = '';

        switch ($action) {
            case 'return':
                $stockDelta = $part;
                $label = $part < $origAbs ? 'Retour stock (partiel)' : 'Retour stock';
                break;
            case 'loss':
                $stockDelta = 0;
                $label = $part < $origAbs ? 'Perte / saisie (partiel)' : 'Perte / saisie';
                if (trim((string) $notes) === '') {
                    return response()->json(['error' => 'Notes obligatoires pour une perte'], 422);
                }
                break;
            case 'gift':
                $stockDelta = 0;
                $label = $part < $origAbs ? 'Don (partiel)' : 'Don';
                if (trim((string) $notes) === '') {
                    return response()->json(['error' => 'Preciser le beneficiaire dans les notes'], 422);
                }
                break;
        }

        $remainder  = $origAbs - $part;
        $noteSuffix = 'attrib. #' . $movement->id . ' · ' . $part . '/' . $origAbs . 'x';

        $reconciliation = DB::transaction(function () use ($movement, $item, $stockDelta, $user, $notes, $label, $reason, $remainder, $noteSuffix) {
            $mv = StockMovement::create([
                'stock_item_id'         => $item->id,
                'quantity_change'       => $stockDelta,
                'reason'                => $reason,
                'user_id'               => $user->id,
                'attributed_to_user_id' => $movement->attributed_to_user_id,
                'notes'                 => trim($label . ' (' . $noteSuffix . '): ' . ($notes ?? '')),
                'created_at'            => now(),
            ]);

            if ($stockDelta !== 0) {
                $item->increment('quantity', $stockDelta);
            }

            if ($remainder > 0) {
                $movement->update([
                    'quantity_change' => -$remainder,
                ]);
            } else {
                $movement->update([
                    'reconciled_at'             => now(),
                    'reconciled_by_movement_id' => $mv->id,
                ]);
            }

            return $mv;
        });

        $msgQty = $part . ($remainder > 0 ? '/' . $origAbs : '') . '× ' . $item->name;

        return response()->json([
            'ok'        => true,
            'message'   => $label . ' enregistre pour ' . $msgQty
                . ($remainder > 0 ? ' — il reste ' . $remainder . ' en attribution' : ''),
            'movement'  => $this->mapMovement($reconciliation->fresh(['user', 'attributedTo'])),
            'remainder' => $remainder,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Validations (Phase 3.4)
    // ─────────────────────────────────────────────────────────

    public function apiValidationsList(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request, self::PAGE_VALIDATIONS)) {
            return $denied;
        }

        $rows = StockMovement::pendingApproval()
            ->where('reason', 'attribution')
            ->with([
                'stockItem:id,slug,name,category,default_sell_price',
                'attributedTo:id,name,role',
                'user:id,name',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get()
            ->map(fn (StockMovement $m) => $this->mapMovement($m, withAttribution: true));

        return response()->json(['validations' => $rows]);
    }

    public function apiApprove(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAccess($request, self::PAGE_VALIDATIONS)) {
            return $denied;
        }
        $user = $this->authUser($request);

        $movement = StockMovement::find($id);
        if (! $movement || $movement->reason !== 'attribution') {
            return response()->json(['error' => 'Mouvement introuvable'], 404);
        }
        if ($movement->approved_at || $movement->rejected_at) {
            return response()->json(['error' => 'Deja traite'], 422);
        }

        $movement->update([
            'approved_by_user_id' => $user->id,
            'approved_at'         => now(),
            'requires_approval'   => false,
        ]);

        return response()->json(['ok' => true, 'message' => 'Attribution approuvee']);
    }

    public function apiReject(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAccess($request, self::PAGE_VALIDATIONS)) {
            return $denied;
        }
        $user = $this->authUser($request);

        $movement = StockMovement::find($id);
        if (! $movement || $movement->reason !== 'attribution') {
            return response()->json(['error' => 'Mouvement introuvable'], 404);
        }
        if ($movement->approved_at || $movement->rejected_at) {
            return response()->json(['error' => 'Deja traite'], 422);
        }

        $v = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $qty = abs((int) $movement->quantity_change);

        DB::transaction(function () use ($movement, $user, $request, $qty) {
            if (! $movement->from_external) {
                StockMovement::create([
                    'stock_item_id'         => $movement->stock_item_id,
                    'quantity_change'       => +$qty,
                    'reason'                => 'adjustment',
                    'user_id'               => $user->id,
                    'attributed_to_user_id' => $movement->attributed_to_user_id,
                    'notes'                 => 'Rejet attribution #' . $movement->id . ' par tresorier : ' . $request->input('reason'),
                    'created_at'            => now(),
                ]);
                $movement->stockItem->increment('quantity', $qty);
            }

            $movement->update([
                'rejected_at'       => now(),
                'rejection_reason'  => $request->input('reason'),
                'requires_approval' => false,
            ]);
        });

        return response()->json([
            'ok'      => true,
            'message' => $movement->from_external
                ? 'Attribution rejetee (hors stock : aucun mouvement de coffre)'
                : 'Attribution rejetee, stock rendu au coffre',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Stock movement (Phase 3B.1)
    // ─────────────────────────────────────────────────────────

    public function apiMovement(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }
        $user = $this->authUser($request);

        $v = Validator::make($request->all(), [
            'stock_item_id' => 'required|integer|exists:stock_items,id',
            'quantity'      => 'required|integer|min:1|max:999999999',
            'direction'     => 'required|in:in,out',
            'reason'        => 'required|string|in:' . implode(',', array_keys(StockMovement::REASONS)),
            'unit_cost'     => 'nullable|integer|min:0|max:999999999',
            'notes'         => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $item = StockItem::find($request->input('stock_item_id'));
        if (! $item || ! $item->is_active) {
            return response()->json(['error' => 'Article indisponible'], 404);
        }

        $qty = (int) $request->input('quantity');
        $direction = $request->input('direction');
        $delta = $direction === 'in' ? $qty : -$qty;
        $reason = $request->input('reason');
        $unitCost = $request->input('unit_cost');
        $notes = $request->input('notes');
        $warning = null;

        if ($direction === 'out' && $item->quantity < $qty) {
            $warning = 'Stock insuffisant (' . $item->quantity . ' en stock). Le stock passera en negatif.';
        }

        DB::transaction(function () use ($item, $delta, $reason, $unitCost, $user, $notes) {
            $item->increment('quantity', $delta);
            StockMovement::create([
                'stock_item_id'   => $item->id,
                'quantity_change' => $delta,
                'reason'          => $reason,
                'unit_cost'       => $reason === 'purchase' ? $unitCost : null,
                'user_id'         => $user->id,
                'notes'           => $notes,
                'created_at'      => now(),
            ]);
        });

        $label = $direction === 'in' ? 'Entree' : 'Sortie';

        return response()->json([
            'ok'      => true,
            'message' => $label . ' de ' . $qty . '× ' . $item->name . ' enregistree',
            'warning' => $warning,
        ]);
    }

    public function apiCreateItem(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }
        $user = $this->authUser($request);

        $categories = array_keys(StockItem::CATEGORIES);

        $v = Validator::make($request->all(), [
            'name'                   => 'required|string|max:120',
            'category'               => 'required|string|in:' . implode(',', $categories),
            'quantity'               => 'nullable|integer|min:0|max:999999999',
            'default_sell_price'     => 'nullable|integer|min:0|max:999999999',
            'default_purchase_price' => 'nullable|integer|min:0|max:999999999',
            'unit_weight_g'          => 'nullable|integer|min:0|max:9999999',
            'notes'                  => 'nullable|string|max:1000',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $name = trim($request->input('name'));
        $category = $request->input('category');
        $slug = \Illuminate\Support\Str::slug($category . '_' . $name, '_');
        if (strlen($slug) > 110) {
            $slug = substr($slug, 0, 110);
        }
        $base = $slug;
        $i = 1;
        while (StockItem::where('slug', $slug)->exists()) {
            $i++;
            $slug = $base . '_' . $i;
        }

        $qty = (int) ($request->input('quantity') ?? 0);

        $item = DB::transaction(function () use ($slug, $name, $category, $qty, $request, $user) {
            $item = StockItem::create([
                'slug'                   => $slug,
                'name'                   => $name,
                'category'               => $category,
                'quantity'               => $qty,
                'default_sell_price'     => $request->input('default_sell_price'),
                'default_purchase_price' => $request->input('default_purchase_price'),
                'unit_weight_g'          => $request->input('unit_weight_g'),
                'is_sellable'            => true,
                'is_active'              => true,
                'sort_order'             => 5000,
                'notes'                  => $request->input('notes'),
            ]);

            if ($qty > 0) {
                StockMovement::create([
                    'stock_item_id'   => $item->id,
                    'quantity_change' => $qty,
                    'reason'          => 'adjustment',
                    'user_id'         => $user->id,
                    'notes'           => 'Creation article avec stock initial de ' . $qty,
                    'created_at'      => now(),
                ]);
            }

            return $item;
        });

        return response()->json([
            'ok'      => true,
            'message' => 'Article "' . $item->name . '" cree (slug: ' . $item->slug . ')',
            'item'    => $this->mapItem($item),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Import CSV (Phase 3.5)
    // ─────────────────────────────────────────────────────────

    public function apiImportPreview(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request, self::PAGE_IMPORT)) {
            return $denied;
        }

        $v = Validator::make($request->all(), [
            'csv' => 'required|string|max:500000',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $rows = $this->parseCsv((string) $request->input('csv'));

        $preview = [];
        $errors  = [];
        foreach ($rows as $n => $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            $qty  = (int) ($row['quantity'] ?? 0);
            if ($slug === '') {
                $errors[] = 'Ligne ' . ($n + 1) . ' : slug manquant';
                continue;
            }
            $item = StockItem::where('slug', $slug)->first();
            if (! $item) {
                $errors[] = 'Ligne ' . ($n + 1) . ' : slug inconnu « ' . $slug . ' »';
                continue;
            }
            $preview[] = [
                'slug'            => $slug,
                'name'            => $item->name,
                'category'        => $item->category,
                'category_label'  => StockItem::CATEGORIES[$item->category] ?? $item->category,
                'current_qty'     => (int) $item->quantity,
                'import_qty'      => $qty,
                'delta'           => $qty - (int) $item->quantity,
            ];
        }

        return response()->json([
            'preview' => $preview,
            'errors'  => $errors,
            'total'   => count($preview),
        ]);
    }

    public function apiImportCommit(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request, self::PAGE_IMPORT)) {
            return $denied;
        }
        $user = $this->authUser($request);

        $v = Validator::make($request->all(), [
            'csv'   => 'required|string|max:500000',
            'label' => 'nullable|string|max:200',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $rows = $this->parseCsv((string) $request->input('csv'));
        $label = trim((string) $request->input('label')) ?: 'Import CSV du ' . now()->format('d/m/Y H:i');

        $applied = 0;
        $errors  = [];

        DB::transaction(function () use ($rows, $user, $label, &$applied, &$errors) {
            foreach ($rows as $n => $row) {
                $slug = trim((string) ($row['slug'] ?? ''));
                $qty  = (int) ($row['quantity'] ?? 0);
                if ($slug === '') {
                    $errors[] = 'Ligne ' . ($n + 1) . ' : slug manquant';
                    continue;
                }
                $item = StockItem::where('slug', $slug)->first();
                if (! $item) {
                    $errors[] = 'Ligne ' . ($n + 1) . ' : slug inconnu « ' . $slug . ' »';
                    continue;
                }
                $current = (int) $item->quantity;
                $delta   = $qty - $current;
                if ($delta === 0) {
                    continue;
                }
                StockMovement::create([
                    'stock_item_id'   => $item->id,
                    'quantity_change' => $delta,
                    'reason'          => 'adjustment',
                    'user_id'         => $user->id,
                    'notes'           => $label . ' (stock ' . $current . ' -> ' . $qty . ')',
                    'created_at'      => now(),
                ]);
                $item->update(['quantity' => $qty]);
                $applied++;
            }
        });

        return response()->json([
            'ok'      => true,
            'applied' => $applied,
            'errors'  => $errors,
            'message' => $applied . ' ligne(s) appliquee(s)' . (count($errors) ? ' · ' . count($errors) . ' erreur(s)' : ''),
        ]);
    }

    /**
     * Very forgiving CSV parser accepting either comma or semicolon delimiters.
     * Expected headers (case-insensitive): slug, quantity. Extra columns are ignored.
     */
    private function parseCsv(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        if (empty($lines)) {
            return [];
        }
        $headerLine = array_shift($lines);
        $delimiter = substr_count($headerLine, ';') > substr_count($headerLine, ',') ? ';' : ',';
        $headers = array_map(fn ($h) => strtolower(trim($h, " \t\"'")), str_getcsv($headerLine, $delimiter));
        $slugIdx = array_search('slug', $headers);
        $qtyIdx  = array_search('quantity', $headers);
        if ($slugIdx === false) {
            $slugIdx = 0;
        }
        if ($qtyIdx === false) {
            $qtyIdx = 1;
        }
        $out = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cols = str_getcsv($line, $delimiter);
            $out[] = [
                'slug'     => $cols[$slugIdx] ?? '',
                'quantity' => $cols[$qtyIdx] ?? 0,
            ];
        }

        return $out;
    }

    // ─────────────────────────────────────────────────────────
    // Mapping helpers
    // ─────────────────────────────────────────────────────────

    private function mapMovement(StockMovement $m, bool $withAttribution = false): array
    {
        $data = [
            'id'              => $m->id,
            'stock_item_id'   => $m->stock_item_id,
            'item_name'       => $m->stockItem->name ?? '?',
            'item_slug'       => $m->stockItem->slug ?? null,
            'category'        => $m->stockItem->category ?? null,
            'reason'          => $m->reason,
            'reason_label'    => StockMovement::REASONS[$m->reason] ?? $m->reason,
            'quantity_change' => (int) $m->quantity_change,
            'quantity_abs'    => abs((int) $m->quantity_change),
            'attribution_original_abs' => $m->reason === 'attribution' && $m->attribution_original_abs !== null
                ? (int) $m->attribution_original_abs
                : null,
            'unit_cost'       => $m->unit_cost,
            'notes'           => $m->notes,
            'by_name'         => $m->user->name ?? '?',
            'by_id'           => $m->user_id,
            'date'            => $m->created_at?->format('d/m H:i'),
            'date_full'       => $m->created_at?->format('d/m/Y H:i'),
        ];

        if ($withAttribution || $m->reason === 'attribution') {
            $data['attributed_to_id']   = $m->attributed_to_user_id;
            $data['attributed_to_name'] = $m->attributedTo->name ?? null;
            $data['attributed_to_role'] = $m->attributedTo->role ?? null;
            $data['default_sell_price'] = $m->stockItem->default_sell_price ?? 0;
            $data['estimated_value']    = (int) (($m->stockItem->default_sell_price ?? 0) * abs($m->quantity_change));
            $data['requires_approval']  = (bool) $m->requires_approval;
            $data['approved_at']        = $m->approved_at?->format('d/m/Y H:i');
            $data['approved_by']        = $m->approvedBy->name ?? null;
            $data['rejected_at']        = $m->rejected_at?->format('d/m/Y H:i');
            $data['rejection_reason']   = $m->rejection_reason;
            $data['reconciled_at']      = $m->reconciled_at?->format('d/m/Y H:i');
            $data['status']             = $m->rejected_at
                ? 'rejected'
                : ($m->reconciled_at
                    ? 'reconciled'
                    : ($m->requires_approval ? 'pending' : 'open'));
            $data['from_external'] = (bool) $m->from_external;
        }

        return $data;
    }
}
