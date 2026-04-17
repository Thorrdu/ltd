<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;
use App\Models\StockItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    private const PAGE_KEY = 'classements';

    public function index()
    {
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('classements', [
            'members'    => $members,
            'categories' => StockItem::CATEGORIES,
        ]);
    }

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

    // ── API : Rankings ──────────────────────────────────────

    public function apiRankings(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $period = $request->query('period', 'week');

        $eligibleCategories = Setting::get('rankings.eligible_categories', ['drug', 'weapon_finished', 'ammo', 'melee', 'drug_raw', 'misc']);
        $criteria           = Setting::get('rankings.criteria', 'revenue');

        // Eligible stock_item IDs
        $eligibleItemIds = StockItem::whereIn('category', $eligibleCategories)->pluck('id');

        // Date range
        [$from, $to] = $this->periodRange($period);

        // Build ranking query
        $query = Sale::query()
            ->whereIn('stock_item_id', $eligibleItemIds)
            ->whereNotNull('sold_by_user_id');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $rows = $query
            ->select(
                'sold_by_user_id',
                DB::raw('COUNT(*) as sale_count'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_price) as total_revenue')
            )
            ->groupBy('sold_by_user_id')
            ->get();

        // Sort by criteria
        $sortKey = match ($criteria) {
            'count'    => 'sale_count',
            'quantity' => 'total_quantity',
            default    => 'total_revenue',
        };
        $sorted = $rows->sortByDesc($sortKey)->values();

        // Attach user info
        $users = User::whereIn('id', $sorted->pluck('sold_by_user_id'))->get()->keyBy('id');
        $rankings = $sorted->map(function ($row, $index) use ($users) {
            $user = $users->get($row->sold_by_user_id);

            return [
                'rank'           => $index + 1,
                'user_id'        => $row->sold_by_user_id,
                'name'           => $user?->name ?? 'Inconnu',
                'role'           => $user?->role ?? '',
                'sale_count'     => (int) $row->sale_count,
                'total_quantity' => (int) $row->total_quantity,
                'total_revenue'  => (int) $row->total_revenue,
            ];
        })->all();

        // Eagle of the week
        $eagle = $this->computeEagle();

        return response()->json([
            'rankings'   => array_values($rankings),
            'criteria'   => $criteria,
            'period'     => $period,
            'eagle'      => $eagle,
            'categories' => $eligibleCategories,
        ]);
    }

    // ── API : Config (officer+) ─────────────────────────────

    public function apiConfig(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $categories = Setting::get('rankings.eligible_categories', []);
        $criteria   = Setting::get('rankings.criteria', 'revenue');

        return response()->json([
            'eligible_categories' => $categories,
            'criteria'            => $criteria,
            'all_categories'      => StockItem::CATEGORIES,
        ]);
    }

    public function apiUpdateConfig(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user = $this->authUser($request);
        if (! $user->isAtLeast('officer')) {
            return response()->json(['error' => 'Officier minimum requis'], 403);
        }

        $categories = $request->input('eligible_categories');
        $criteria   = $request->input('criteria');

        if (is_array($categories)) {
            // Validate each category
            $valid = array_intersect($categories, StockItem::CATEGORIES);
            Setting::set('rankings.eligible_categories', json_encode(array_values($valid)));
        }

        if ($criteria && in_array($criteria, ['revenue', 'count', 'quantity'])) {
            Setting::set('rankings.criteria', $criteria);
        }

        Setting::clearCache();

        return response()->json(['ok' => true]);
    }

    // ── API : Eagle history ─────────────────────────────────

    public function apiEagleHistory(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $weeks = [];
        $now   = Carbon::now();

        // Last 12 weeks
        for ($i = 1; $i <= 12; $i++) {
            $weekStart = $now->copy()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

            $eagle = $this->computeEagleForWeek($weekStart, $weekEnd);
            if ($eagle) {
                $weeks[] = [
                    'week_label' => $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m/Y'),
                    'name'       => $eagle['name'],
                    'score'      => $eagle['score'],
                    'criteria'   => $eagle['criteria'],
                ];
            }
        }

        return response()->json(['history' => $weeks]);
    }

    // ── Helpers ─────────────────────────────────────────────

    private function periodRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'week'      => [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfWeek(Carbon::SUNDAY)],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(Carbon::MONDAY), $now->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)],
            'month'     => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'all'       => [null, null],
            default     => [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfWeek(Carbon::SUNDAY)],
        };
    }

    private function computeEagle(): ?array
    {
        $now       = Carbon::now();
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $now->copy()->endOfWeek(Carbon::SUNDAY);

        return $this->computeEagleForWeek($weekStart, $weekEnd);
    }

    private function computeEagleForWeek(Carbon $from, Carbon $to): ?array
    {
        $eligibleCategories = Setting::get('rankings.eligible_categories', ['drug', 'weapon_finished', 'ammo', 'melee', 'drug_raw', 'misc']);
        $criteria           = Setting::get('rankings.criteria', 'revenue');
        $eligibleItemIds    = StockItem::whereIn('category', $eligibleCategories)->pluck('id');

        $scoreExpr = match ($criteria) {
            'count'    => 'COUNT(*)',
            'quantity' => 'SUM(quantity)',
            default    => 'SUM(total_price)',
        };

        $top = Sale::query()
            ->whereIn('stock_item_id', $eligibleItemIds)
            ->whereNotNull('sold_by_user_id')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('sold_by_user_id, ' . $scoreExpr . ' as score')
            ->groupBy('sold_by_user_id')
            ->orderByDesc('score')
            ->first();

        if (! $top) {
            return null;
        }

        $user = User::find($top->sold_by_user_id);

        return [
            'user_id'  => $top->sold_by_user_id,
            'name'     => $user?->name ?? 'Inconnu',
            'score'    => (int) $top->score,
            'criteria' => $criteria,
        ];
    }
}
