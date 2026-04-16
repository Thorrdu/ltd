<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
            'sales'  => $sales,
            'totals' => $totals,
            'scope'  => $scope,
            'period' => $period,
        ]);
    }

    public function apiCatalog(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        return response()->json([
            'catalog'    => $this->loadCatalog(),
            'categories' => StockItem::CATEGORIES,
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
            'quantity'       => 'required|integer|min:1|max:9999',
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

        if ($request->filled('stock_item_id')) {
            $item = StockItem::find($request->input('stock_item_id'));
            if (! $item || ! $item->is_active) {
                return response()->json(['error' => 'Article indisponible'], 404);
            }
            if (! $item->is_sellable || ! in_array($item->category, self::SELLABLE_CATEGORIES, true)) {
                return response()->json(['error' => 'Cet article n\'est pas vendable depuis /ventes'], 422);
            }
        } else {
            // Mode ad-hoc : l'article n'est pas dans le catalogue. On le cree
            // a la volee (stock initial 0, sera decremente par la vente et
            // passera en negatif), puis on procede comme d'habitude. Le tresorier
            // pourra ensuite ajuster / regulariser via /stocks.
            if ($attributionId) {
                return response()->json(['error' => 'Impossible de reconcilier une attribution avec un article ad-hoc'], 422);
            }
            $name = trim((string) $request->input('ad_hoc_name'));
            $category = (string) $request->input('ad_hoc_category', 'misc');
            if ($name === '') {
                return response()->json(['error' => 'Nom de l\'article requis (mode hors catalogue)'], 422);
            }
            if (! in_array($category, self::SELLABLE_CATEGORIES, true)) {
                $category = 'misc';
            }
            $item = $this->createAdHocStockItem($name, $category);
            $warning = 'Article hors catalogue cree (' . $item->slug . '). Stock initial 0 : passera en negatif apres la vente.';
        }

        $qty   = (int) $request->input('quantity');
        $total = (int) $request->input('total_price');
        $unit  = (int) round($total / max($qty, 1));

        // If this sale reconciles an attribution, validate it and skip stock decrement
        // (the stock was already decremented when the attribution was created).
        $attribution = null;
        if ($attributionId) {
            $attribution = StockMovement::where('id', $attributionId)
                ->where('reason', 'attribution')
                ->whereNull('reconciled_at')
                ->whereNull('rejected_at')
                ->first();
            if (! $attribution) {
                return response()->json(['error' => 'Attribution invalide ou deja reconciliee'], 422);
            }
            if ($attribution->attributed_to_user_id !== $user->id) {
                return response()->json(['error' => 'Cette attribution n\'est pas la votre'], 403);
            }
            if ($attribution->stock_item_id !== $item->id) {
                return response()->json(['error' => 'Article incoherent avec l\'attribution'], 422);
            }
            if ((int) $qty !== abs((int) $attribution->quantity_change)) {
                return response()->json(['error' => 'Quantite differente de l\'attribution'], 422);
            }
        }

        // Toute vente décremente le stock du stock_item + crée un mouvement sale.
        // Si la vente reconcilie une attribution, le stock a deja ete decremente a l'attribution.
        $saleMovement = null;
        if (! $attribution) {
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
        } else {
            // Document the reconciliation with a zero-qty sale movement for traceability.
            $saleMovement = StockMovement::create([
                'stock_item_id'         => $item->id,
                'quantity_change'       => 0,
                'reason'                => 'sale',
                'user_id'               => $user->id,
                'attributed_to_user_id' => $user->id,
                'notes'                 => 'Vente sur attribution #' . $attribution->id . ': ' . $qty . '× ' . $item->name . ' → ' . $request->buyer_name,
                'created_at'            => now(),
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

        // Mark the attribution as reconciled.
        if ($attribution) {
            $attribution->update([
                'reconciled_at'             => now(),
                'reconciled_by_movement_id' => $saleMovement?->id,
            ]);
        }

        return response()->json([
            'ok'      => true,
            'message' => $qty . '× ' . $item->name . ' vendu(s) à ' . $request->input('buyer_name')
                . ($attribution ? ' (attribution reconciliée)' : ''),
            'warning' => $warning,
            'sale'    => $this->mapSale($sale->fresh(['soldBy', 'stockItem'])),
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────

    /**
     * Cree un stock_item a la volee pour un article vendu mais pas encore
     * encode dans le catalogue. Utilise un slug unique derivé du nom.
     * L'item est cree avec quantity=0 : la vente le fera passer en negatif,
     * signalant qu'une regularisation est necessaire via /stocks.
     */
    private function createAdHocStockItem(string $name, string $category): StockItem
    {
        $base = 'adhoc_' . Str::slug($name, '_');
        if (strlen($base) > 100) {
            $base = substr($base, 0, 100);
        }
        $slug = $base;
        $i = 1;
        while (StockItem::where('slug', $slug)->exists()) {
            $i++;
            $slug = $base . '_' . $i;
        }

        return StockItem::create([
            'category'           => $category,
            'slug'               => $slug,
            'name'               => $name,
            'quantity'           => 0,
            'default_sell_price' => null,
            'is_sellable'        => true,
            'is_active'          => true,
            'sort_order'         => 9000,
            'notes'              => 'Cree automatiquement depuis /ventes (article hors catalogue).',
        ]);
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
                'notes'              => $i->notes,
            ])
            ->values();
    }

    private function mapSale(Sale $s): array
    {
        $item = $s->stockItem;
        $cat  = $item?->category ?? 'misc';

        return [
            'id'            => $s->id,
            'stock_item_id' => $s->stock_item_id,
            'category'      => $cat,
            'type_short'    => self::TYPE_SHORT[$cat] ?? $cat,
            'item_name'     => $item?->name ?? '?',
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
