<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaleController extends Controller
{
    private const PAGE_KEY = 'ventes_rapides';

    /**
     * Categories affichees dans le select de /ventes. Toutes les categories du catalogue
     * sont vendables : un membre peut revendre du stock brut (matières, pièces, plans)
     * comme des produits finis. Le flag `is_sellable` sur l'item permet de desactiver
     * individuellement (ex : un plan consommé).
     */
    private const SELLABLE_CATEGORIES = [
        'weapon_finished', 'weapon_plan', 'weapon_piece', 'raw_material',
        'ammo', 'melee', 'drug', 'drug_raw', 'farm_consumable', 'tool',
        'electronic', 'misc',
    ];

    /**
     * Mapping category -> label court pour les badges de type de vente.
     */
    private const TYPE_SHORT = [
        'weapon_finished' => 'Arme',
        'weapon_plan'     => 'Plan',
        'weapon_piece'    => 'Pièce',
        'raw_material'    => 'Matière',
        'ammo'            => 'Munition',
        'melee'           => 'Arme blanche',
        'drug'            => 'Drogue',
        'drug_raw'        => 'Drogue (matière)',
        'farm_consumable' => 'Consommable',
        'tool'            => 'Outil',
        'electronic'      => 'Électronique',
        'misc'            => 'Divers',
    ];

    public function index()
    {
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('ventes', [
            'members'     => $members,
            'catalogJson' => $this->loadCatalog()->toJson(),
        ]);
    }

    // ── Auth ────────────────────────────────────────────────

    private function authUser(Request $request): ?User
    {
        $userId = $request->header('X-Sim-User');

        return $userId ? User::find($userId) : null;
    }

    private function requireAccess(Request $request): ?JsonResponse
    {
        $user = $this->authUser($request);
        if (! $user) {
            return response()->json(['error' => 'unauthorized'], 403);
        }
        if (! $user->canAccessPage(self::PAGE_KEY)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        return null;
    }

    // ── API ─────────────────────────────────────────────────

    public function apiList(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user   = $this->authUser($request);
        $scope  = $request->query('scope', 'mine');
        $period = $request->query('period', 'today');

        $base = Sale::query();
        if ($scope === 'mine') {
            $base->where('sold_by_user_id', $user->id);
        }
        $base->inPeriod($period);

        $sales = (clone $base)
            ->with(['soldBy', 'validatedBy', 'stockItem'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(fn (Sale $s) => $this->mapSale($s));

        $totals = [
            'count'    => (clone $base)->count(),
            'revenue'  => (int) (clone $base)->sum('total_price'),
            'quantity' => (int) (clone $base)->sum('quantity'),
        ];

        return response()->json([
            'sales'     => $sales,
            'totals'    => $totals,
            'scope'     => $scope,
            'period'    => $period,
            'user_role' => $user->role,
        ]);
    }

    public function apiCatalog(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user = $this->authUser($request);

        return response()->json([
            'catalog'    => $user->isOfficer() ? $this->loadCatalog() : collect([]),
            'categories' => StockItem::CATEGORIES,
            'user_role'  => $user->role,
        ]);
    }

    public function apiCreate(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user = $this->authUser($request);

        $v = Validator::make($request->all(), [
            'stock_item_id'  => 'nullable|integer|exists:stock_items,id',
            'quantity'       => 'required|integer|min:1|max:999999999',
            'total_price'    => 'required|integer|min:0',
            'buyer_name'     => 'required|string|max:100',
            'notes'          => 'nullable|string|max:500',
            'attribution_id' => 'nullable|integer|exists:stock_movements,id',
            // Article hors catalogue : fourni quand stock_item_id est vide.
            // Permet de declarer une vente d'un article non encore encode.
            'ad_hoc_name'     => 'nullable|string|max:120',
            'ad_hoc_category' => 'nullable|string|in:' . implode(',', self::SELLABLE_CATEGORIES),
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $warning = null;
        $attributionId = $request->input('attribution_id');
        $item = null;
        $adHocLabel = null;

        if ($request->filled('stock_item_id')) {
            $item = StockItem::find($request->input('stock_item_id'));
            if (! $item || ! $item->is_active) {
                return response()->json(['error' => 'Article indisponible'], 404);
            }
            if (! $item->is_sellable || ! in_array($item->category, self::SELLABLE_CATEGORIES, true)) {
                return response()->json(['error' => 'Cet article n\'est pas vendable depuis /ventes'], 422);
            }
        } else {
            // Mode vente libre (service, information, etc.) : pas de stock_item,
            // juste un libelle sur la sale pour la comptabilite.
            if ($attributionId) {
                return response()->json(['error' => 'Impossible de reconcilier une attribution avec un article hors stock'], 422);
            }
            $adHocLabel = trim((string) $request->input('ad_hoc_name'));
            if ($adHocLabel === '') {
                return response()->json(['error' => 'Nom de l\'article ou du service requis (mode hors stock)'], 422);
            }
        }

        $qty   = (int) $request->input('quantity');
        $total = (int) $request->input('total_price');
        $unit  = (int) round($total / max($qty, 1));

        // Attribution resolution: explicit attribution_id, OR auto-detect all for this item.
        $hasAttribution = false;
        $totalAttribAvailable = 0;

        if ($attributionId && $item) {
            // Explicit attribution: validate it.
            $explicit = StockMovement::where('id', $attributionId)
                ->where('reason', 'attribution')
                ->whereNull('reconciled_at')
                ->whereNull('rejected_at')
                ->first();
            if (! $explicit) {
                return response()->json(['error' => 'Attribution invalide ou deja reconciliee'], 422);
            }
            if ($explicit->attributed_to_user_id !== $user->id) {
                return response()->json(['error' => 'Cette attribution n\'est pas la votre'], 403);
            }
            if ($explicit->stock_item_id !== $item->id) {
                return response()->json(['error' => 'Article incoherent avec l\'attribution'], 422);
            }
            $hasAttribution = true;
        } elseif ($item) {
            // Auto-detect: check if the user has any open attributions for this item.
            $anyAttrib = StockMovement::openAttribution()
                ->where('attributed_to_user_id', $user->id)
                ->where('stock_item_id', $item->id)
                ->exists();
            $hasAttribution = $anyAttrib;
        }

        // ── Vente hors stock (service, info) : pas de mouvement, juste la sale ──
        if (! $item) {
            $sale = Sale::create([
                'stock_item_id'   => null,
                'ad_hoc_label'    => $adHocLabel,
                'quantity'        => $qty,
                'unit_price'      => $unit,
                'total_price'     => $total,
                'buyer_name'      => $request->input('buyer_name'),
                'sold_by_user_id' => $user->id,
                'notes'           => $request->input('notes'),
            ]);

            return response()->json([
                'ok'      => true,
                'message' => $qty . '× ' . $adHocLabel . ' vendu(s) à ' . $request->input('buyer_name') . ' (hors stock)',
                'warning' => null,
                'sale'    => $this->mapSale($sale->fresh(['soldBy', 'stockItem'])),
                'attribution_remaining' => null,
            ]);
        }

        // ── Vente standard (article en stock) ──
        $saleMovement = null;
        $attributionRemaining = null;
        $itemLabel = $item->name;

        if ($hasAttribution) {
            // Consume from open attributions (FIFO), then deduct remainder from central stock.
            $result = $this->reconcileAttributions($user, $item, $qty, $request->input('buyer_name'));
            $fromStock = $result['from_stock'];
            $attributionRemaining = $result['attribution_remaining'];

            if ($fromStock > 0) {
                if ($item->quantity < $fromStock && $warning === null) {
                    $warning = 'Stock insuffisant (' . $item->quantity . ' en stock). Le stock passe en négatif.';
                }
                $item->decrement('quantity', $fromStock);
                StockMovement::create([
                    'stock_item_id'   => $item->id,
                    'quantity_change' => -$fromStock,
                    'reason'          => 'sale',
                    'user_id'         => $user->id,
                    'notes'           => 'Complement stock (au-dela attributions): ' . $fromStock . '× ' . $item->name . ' → ' . $request->buyer_name,
                    'created_at'      => now(),
                ]);
            }
        } else {
            // No attribution: deduct entirely from central stock.
            if ($item->quantity < $qty && $warning === null) {
                $warning = 'Stock insuffisant (' . $item->quantity . ' en stock). Le stock passe en négatif.';
            }
            $item->decrement('quantity', $qty);
            $saleMovement = StockMovement::create([
                'stock_item_id'   => $item->id,
                'quantity_change' => -$qty,
                'reason'          => 'sale',
                'user_id'         => $user->id,
                'notes'           => 'Vente rapide: ' . $qty . '× ' . $item->name . ' → ' . $request->buyer_name,
                'created_at'      => now(),
            ]);
        }

        $sale = Sale::create([
            'stock_item_id'   => $item->id,
            'quantity'        => $qty,
            'unit_price'      => $unit,
            'total_price'     => $total,
            'buyer_name'      => $request->input('buyer_name'),
            'sold_by_user_id' => $user->id,
            'notes'           => $request->input('notes'),
        ]);

        return response()->json([
            'ok'      => true,
            'message' => $qty . '× ' . $itemLabel . ' vendu(s) à ' . $request->input('buyer_name')
                . ($hasAttribution
                    ? ($attributionRemaining > 0
                        ? ' (il reste ' . $attributionRemaining . ' sur vos attributions)'
                        : ' (attributions reconciliées)')
                    : ''),
            'warning' => $warning,
            'sale'    => $this->mapSale($sale->fresh(['soldBy', 'stockItem'])),
            'attribution_remaining' => $attributionRemaining,
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────

    /**
     * Return open attributions for the authenticated user that are quick-sale items.
     * These are items the member currently has on them and can sell directly.
     */
    public function apiMyAttributions(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user = $this->authUser($request);

        $raw = StockMovement::openAttribution()
            ->where('attributed_to_user_id', $user->id)
            ->with(['stockItem'])
            ->get()
            ->filter(fn (StockMovement $m) => $m->stockItem && $m->stockItem->is_active);

        // Group by stock_item_id: cumulate quantities across multiple attributions.
        $grouped = $raw->groupBy('stock_item_id')->map(function ($movements) {
            $first = $movements->first();
            $item  = $first->stockItem;
            $totalQty = $movements->sum(fn ($m) => abs((int) $m->quantity_change));
            $ids = $movements->pluck('id')->values()->toArray();

            return [
                'attribution_ids'    => $ids,
                'stock_item_id'      => $item->id,
                'category'           => $item->category,
                'category_label'     => StockItem::CATEGORIES[$item->category] ?? $item->category,
                'type_short'         => self::TYPE_SHORT[$item->category] ?? $item->category,
                'name'               => $item->name,
                'slug'               => $item->slug,
                'quantity'           => $totalQty,
                'default_sell_price' => $item->default_sell_price,
            ];
        })->values();

        return response()->json(['attributions' => $grouped]);
    }

    // ── Helpers (private) ───────────────────────────────────

    /**
     * Reconcile open attributions for an item FIFO. Returns how many units
     * remain to be deducted from central stock, and how many units are still
     * open on the member's attributions.
     *
     * @return array{from_stock: int, attribution_remaining: int}
     */
    private function reconcileAttributions(User $user, StockItem $item, int $qty, string $buyerName): array
    {
        $attributions = StockMovement::openAttribution()
            ->where('attributed_to_user_id', $user->id)
            ->where('stock_item_id', $item->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $remaining = $qty;

        foreach ($attributions as $attrib) {
            if ($remaining <= 0) {
                break;
            }

            $attribQty = abs((int) $attrib->quantity_change);
            $consume = min($remaining, $attribQty);
            $remaining -= $consume;

            // Traceability movement.
            $mvt = StockMovement::create([
                'stock_item_id'         => $item->id,
                'quantity_change'       => 0,
                'reason'                => 'sale',
                'user_id'               => $user->id,
                'attributed_to_user_id' => $user->id,
                'notes'                 => 'Vente sur attribution #' . $attrib->id
                    . ($consume < $attribQty ? ' (partiel ' . $consume . '/' . $attribQty . ')' : '')
                    . ': ' . $consume . '× ' . $item->name . ' → ' . $buyerName,
                'created_at'            => now(),
            ]);

            $newRemainder = $attribQty - $consume;
            if ($newRemainder > 0) {
                $attrib->update(['quantity_change' => -$newRemainder]);
            } else {
                $attrib->update([
                    'reconciled_at'             => now(),
                    'reconciled_by_movement_id' => $mvt->id,
                ]);
            }
        }

        // How many units are still attributed (across all remaining open attributions)?
        $stillOpen = StockMovement::openAttribution()
            ->where('attributed_to_user_id', $user->id)
            ->where('stock_item_id', $item->id)
            ->sum(DB::raw('ABS(quantity_change)'));

        return [
            'from_stock'            => $remaining, // units that must come from central stock
            'attribution_remaining' => (int) $stillOpen,
        ];
    }

    private function loadCatalog()
    {
        return StockItem::active()
            ->sellable()
            ->whereIn('category', self::SELLABLE_CATEGORIES)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (StockItem $i) => [
                'id'                 => $i->id,
                'category'           => $i->category,
                'category_label'     => StockItem::CATEGORIES[$i->category] ?? $i->category,
                'type_short'         => self::TYPE_SHORT[$i->category] ?? $i->category,
                'name'               => $i->name,
                'slug'               => $i->slug,
                'default_sell_price' => $i->default_sell_price,
                'unit_weight_g'      => $i->unit_weight_g,
                'current_stock'      => $i->quantity,
                'is_quick_sale'      => (bool) $i->is_quick_sale,
                'notes'              => $i->notes,
            ])
            ->values();
    }

    /**
     * Batch sale: create multiple sales at once (one per item), all with
     * the same buyer, notes, and seller. Used by the "Vente Express" UI.
     */
    public function apiBatch(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user = $this->authUser($request);

        $v = Validator::make($request->all(), [
            'items'           => 'required|array|min:1|max:100',
            'items.*.stock_item_id' => 'required|integer|exists:stock_items,id',
            'items.*.quantity'      => 'required|integer|min:1|max:999999999',
            'actual_amount'   => 'nullable|integer|min:0',
            'buyer_name'      => 'required|string|max:100',
            'notes'           => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $items = $request->input('items');
        $buyerName = $request->input('buyer_name');
        $notes = $request->input('notes');
        $actualAmount = $request->input('actual_amount');

        $sales = [];
        $warnings = [];
        $totalTheoretical = 0;

        DB::transaction(function () use ($items, $buyerName, $notes, $user, &$sales, &$warnings, &$totalTheoretical) {
            foreach ($items as $line) {
                $item = StockItem::find($line['stock_item_id']);
                if (! $item || ! $item->is_active) {
                    $warnings[] = 'Article #' . $line['stock_item_id'] . ' introuvable';
                    continue;
                }

                $qty = (int) $line['quantity'];
                $unitPrice = (int) ($item->default_sell_price ?? 0);
                $lineTotal = $unitPrice * $qty;
                $totalTheoretical += $lineTotal;

                // Auto-deduct from all open attributions (FIFO), then from central stock.
                $result = $this->reconcileAttributions($user, $item, $qty, $buyerName);
                $fromStock = $result['from_stock'];

                if ($fromStock > 0) {
                    if ($item->quantity < $fromStock) {
                        $warnings[] = $item->name . ' : stock insuffisant (' . $item->quantity . ')';
                    }
                    $item->decrement('quantity', $fromStock);
                    StockMovement::create([
                        'stock_item_id'   => $item->id,
                        'quantity_change' => -$fromStock,
                        'reason'          => 'sale',
                        'user_id'         => $user->id,
                        'notes'           => 'Vente express: ' . $fromStock . '× ' . $item->name . ' → ' . $buyerName,
                        'created_at'      => now(),
                    ]);
                }

                $sale = Sale::create([
                    'stock_item_id'   => $item->id,
                    'quantity'        => $qty,
                    'unit_price'      => $unitPrice,
                    'total_price'     => $lineTotal,
                    'buyer_name'      => $buyerName,
                    'sold_by_user_id' => $user->id,
                    'notes'           => $notes,
                ]);

                $sales[] = [
                    'item_name' => $item->name,
                    'quantity'  => $qty,
                    'total'     => $lineTotal,
                ];
            }
        });

        $actualNote = '';
        if ($actualAmount !== null && $actualAmount != $totalTheoretical) {
            $actualNote = ' (encaissé: $' . number_format($actualAmount, 0, '.', ' ') . ')';
        }

        return response()->json([
            'ok'        => true,
            'message'   => count($sales) . ' article(s) vendu(s) à ' . $buyerName . $actualNote,
            'sales'     => $sales,
            'total'     => $totalTheoretical,
            'actual'    => $actualAmount,
            'warnings'  => $warnings,
        ]);
    }

    private function mapSale(Sale $s): array
    {
        $item = $s->stockItem;
        $cat  = $item?->category ?? 'misc';
        $isAdHoc = $s->stock_item_id === null;

        return [
            'id'            => $s->id,
            'stock_item_id' => $s->stock_item_id,
            'ad_hoc'        => $isAdHoc,
            'category'      => $isAdHoc ? 'service' : $cat,
            'type_short'    => $isAdHoc ? 'Hors stock' : (self::TYPE_SHORT[$cat] ?? $cat),
            'item_name'     => $isAdHoc ? ($s->ad_hoc_label ?? 'Vente libre') : ($item?->name ?? '?'),
            'quantity'      => $s->quantity,
            'unit_price'    => $s->unit_price,
            'total_price'   => $s->total_price,
            'buyer'         => $s->buyer_name,
            'sold_by'       => $s->soldBy->name ?? '?',
            'sold_by_id'    => $s->sold_by_user_id,
            'validated_by'  => $s->validatedBy->name ?? null,
            'notes'         => $s->notes,
            'date'          => $s->created_at?->format('d/m H:i'),
            'date_full'     => $s->created_at?->format('d/m/Y H:i'),
        ];
    }
}
