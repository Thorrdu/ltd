<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StockItem;
use App\Models\User;
use App\Models\WeaponStock;
use App\Models\WeaponStockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SaleController extends Controller
{
    private const PAGE_KEY = 'ventes_rapides';

    /**
     * Categories exposed in the /ventes search (sellable items only).
     * The catalog may contain plans/pieces/raw_material but those are not sellable via /ventes.
     */
    private const SELLABLE_CATEGORIES = ['weapon_finished', 'ammo', 'melee', 'drug', 'drug_raw'];

    public function index()
    {
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('ventes', [
            'members' => $members,
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

        $user = $this->authUser($request);
        $scope = $request->query('scope', 'mine');
        $period = $request->query('period', 'today');

        $base = Sale::query();
        if ($scope === 'mine') {
            $base->where('sold_by_user_id', $user->id);
        }
        if ($period === 'today') {
            $base->whereDate('created_at', now()->toDateString());
        } elseif ($period === 'week') {
            $base->where('created_at', '>=', now()->startOfWeek());
        } elseif ($period === 'month') {
            $base->where('created_at', '>=', now()->startOfMonth());
        }

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
            'types'  => Sale::TYPES,
        ]);
    }

    public function apiCatalog(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        return response()->json([
            'catalog' => $this->loadCatalog(),
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
            'stock_item_id' => 'required|integer|exists:stock_items,id',
            'quantity'      => 'required|integer|min:1|max:9999',
            'total_price'   => 'required|integer|min:0',
            'buyer_name'    => 'required|string|max:100',
            'notes'         => 'nullable|string|max:500',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $item = StockItem::find($request->input('stock_item_id'));
        if (! $item || ! $item->is_active) {
            return response()->json(['error' => 'Item indisponible'], 404);
        }
        if (! in_array($item->category, self::SELLABLE_CATEGORIES, true)) {
            return response()->json(['error' => 'Cet item n\'est pas vendable depuis /ventes'], 422);
        }

        $qty   = (int) $request->input('quantity');
        $total = (int) $request->input('total_price');
        $unit  = (int) round($total / max($qty, 1));

        $warning = null;

        if ($item->category === 'weapon_finished' && $item->weapon_id) {
            $weaponStock = WeaponStock::where('slug', 'weapon_' . optional($item->weapon)->slug)->first();
            if (! $weaponStock || $weaponStock->quantity < $qty) {
                $have = $weaponStock->quantity ?? 0;
                $warning = "Stock insuffisant ({$have} en stock). Le stock passe en négatif.";
            }
            if ($weaponStock) {
                $weaponStock->decrement('quantity', $qty);
                WeaponStockMovement::create([
                    'weapon_stock_id' => $weaponStock->id,
                    'quantity_change' => -$qty,
                    'reason'          => 'sale',
                    'user_id'         => $user->id,
                    'notes'           => 'Vente rapide: ' . $qty . '× ' . $item->name . ' → ' . $request->buyer_name,
                    'created_at'      => now(),
                ]);
            }
        }

        $sale = Sale::create([
            'item_type'        => StockItem::CATEGORY_TO_SALE_TYPE[$item->category] ?? 'other',
            'item_id'          => $item->weapon_id,
            'stock_item_id'    => $item->id,
            'item_name'        => $item->name,
            'quantity'         => $qty,
            'unit_price'       => $unit,
            'total_price'      => $total,
            'buyer_name'       => $request->input('buyer_name'),
            'sold_by_user_id'  => $user->id,
            'notes'            => $request->input('notes'),
        ]);

        return response()->json([
            'ok'      => true,
            'message' => $qty . '× ' . $item->name . ' vendu(s) à ' . $request->input('buyer_name'),
            'warning' => $warning,
            'sale'    => $this->mapSale($sale->fresh(['soldBy', 'stockItem'])),
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────

    /**
     * Returns the active sellable catalog grouped by category.
     * Returns a collection of plain arrays (ready for JSON).
     */
    private function loadCatalog()
    {
        return StockItem::active()
            ->whereIn('category', self::SELLABLE_CATEGORIES)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (StockItem $i) => [
                'id'                 => $i->id,
                'category'           => $i->category,
                'category_label'     => $i->category_label,
                'sale_type'          => $i->sale_type,
                'name'               => $i->name,
                'slug'               => $i->slug,
                'default_sell_price' => $i->default_sell_price,
                'unit_weight_g'      => $i->unit_weight_g,
                'weapon_id'          => $i->weapon_id,
                'notes'              => $i->notes,
            ])
            ->values();
    }

    private function mapSale(Sale $s): array
    {
        return [
            'id'            => $s->id,
            'item_type'     => $s->item_type,
            'type_label'    => Sale::TYPES[$s->item_type] ?? $s->item_type,
            'item_id'       => $s->item_id,
            'stock_item_id' => $s->stock_item_id,
            'item_name'     => $s->item_name,
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
