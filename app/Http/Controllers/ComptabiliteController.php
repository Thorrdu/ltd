<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\McRequest;
use App\Models\Sale;
use App\Models\StockItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComptabiliteController extends Controller
{
    private const PAGE_KEY = 'comptabilite';

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

    public function index()
    {
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('comptabilite', [
            'members' => $members,
        ]);
    }

    // ── API : Dashboard summary ──────────────────────────────

    public function apiSummary(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $period = $request->query('period', 'week');

        // Soldes (stock argent)
        $argentSale = StockItem::where('slug', 'argent_sale')->value('quantity') ?? 0;
        $argentPropre = StockItem::where('slug', 'argent_propre')->value('quantity') ?? 0;

        // Sales
        $salesQuery = Sale::query();
        $this->applyPeriod($salesQuery, $period);
        $salesRevenue = (int) (clone $salesQuery)->sum('total_price');
        $salesCount = (clone $salesQuery)->count();

        // Cotisations
        $cotisQuery = Cotisation::query();
        $this->applyPeriodDate($cotisQuery, $period, 'period_start');
        $cotisDue = (int) (clone $cotisQuery)->sum('amount_due');
        $cotisPaid = (int) (clone $cotisQuery)->sum('amount_paid');

        // Demandes approuvees
        $reqQuery = McRequest::where('status', 'approved');
        $this->applyPeriod($reqQuery, $period, 'handled_at');
        $reqTotal = (int) (clone $reqQuery)->sum('amount');
        $reqCount = (clone $reqQuery)->count();

        // Demandes en attente
        $pendingReqCount = McRequest::where('status', 'pending')->count();
        $pendingReqTotal = (int) McRequest::where('status', 'pending')->sum('amount');

        return response()->json([
            'soldes' => [
                'argent_sale'   => $argentSale,
                'argent_propre' => $argentPropre,
            ],
            'ventes' => [
                'revenue' => $salesRevenue,
                'count'   => $salesCount,
            ],
            'cotisations' => [
                'due'  => $cotisDue,
                'paid' => $cotisPaid,
            ],
            'depenses' => [
                'approved_total' => $reqTotal,
                'approved_count' => $reqCount,
                'pending_total'  => $pendingReqTotal,
                'pending_count'  => $pendingReqCount,
            ],
            'period' => $period,
        ]);
    }

    // ── API : Weekly breakdown ───────────────────────────────

    public function apiWeekly(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $weeks = (int) $request->query('weeks', 8);
        $weeks = min($weeks, 52);

        $data = [];
        for ($i = 0; $i < $weeks; $i++) {
            $start = now()->startOfWeek(Carbon::MONDAY)->subWeeks($i);
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

            $salesRev = (int) Sale::whereBetween('created_at', [$start, $end])->sum('total_price');
            $cotisPaid = (int) Cotisation::where('period_start', $start->toDateString())->sum('amount_paid');
            $depenses = (int) McRequest::where('status', 'approved')
                ->whereBetween('handled_at', [$start, $end])
                ->sum('amount');

            $data[] = [
                'week_label' => $start->format('d/m') . ' - ' . $end->format('d/m'),
                'start'      => $start->toDateString(),
                'ventes'     => $salesRev,
                'cotisations' => $cotisPaid,
                'depenses'   => $depenses,
                'balance'    => $salesRev + $cotisPaid - $depenses,
            ];
        }

        return response()->json(['weeks' => $data]);
    }

    // ── API : Recent transactions ────────────────────────────

    public function apiTransactions(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $limit = min((int) $request->query('limit', 50), 200);
        $type = $request->query('type', 'all'); // all | ventes | depenses | cotisations

        $transactions = collect();

        if ($type === 'all' || $type === 'ventes') {
            $sales = Sale::with(['soldBy', 'stockItem'])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (Sale $s) => [
                    'type'   => 'vente',
                    'label'  => ($s->stockItem?->name ?? $s->ad_hoc_label ?? 'Article') . ' x' . $s->quantity,
                    'amount' => $s->total_price,
                    'sign'   => '+',
                    'user'   => $s->soldBy?->name ?? 'Inconnu',
                    'date'   => $s->created_at->format('d/m/Y H:i'),
                    'ts'     => $s->created_at->timestamp,
                ]);
            $transactions = $transactions->merge($sales);
        }

        if ($type === 'all' || $type === 'depenses') {
            $reqs = McRequest::with(['user'])
                ->where('status', 'approved')
                ->orderByDesc('handled_at')
                ->limit($limit)
                ->get()
                ->map(fn (McRequest $r) => [
                    'type'   => 'depense',
                    'label'  => McRequest::CATEGORIES[$r->category] ?? $r->category,
                    'amount' => $r->amount,
                    'sign'   => '-',
                    'user'   => $r->user?->name ?? 'Inconnu',
                    'date'   => $r->handled_at?->format('d/m/Y H:i') ?? $r->created_at->format('d/m/Y H:i'),
                    'ts'     => ($r->handled_at ?? $r->created_at)->timestamp,
                ]);
            $transactions = $transactions->merge($reqs);
        }

        if ($type === 'all' || $type === 'cotisations') {
            $cotis = Cotisation::with(['user'])
                ->whereNotNull('paid_at')
                ->where('amount_paid', '>', 0)
                ->orderByDesc('paid_at')
                ->limit($limit)
                ->get()
                ->map(fn (Cotisation $c) => [
                    'type'   => 'cotisation',
                    'label'  => 'Cotisation ' . $c->period_start->format('d/m') . '-' . $c->period_end->format('d/m'),
                    'amount' => $c->amount_paid,
                    'sign'   => '+',
                    'user'   => $c->user?->name ?? 'Inconnu',
                    'date'   => $c->paid_at->format('d/m/Y H:i'),
                    'ts'     => $c->paid_at->timestamp,
                ]);
            $transactions = $transactions->merge($cotis);
        }

        $transactions = $transactions->sortByDesc('ts')->take($limit)->values();

        return response()->json(['transactions' => $transactions]);
    }

    // ── Helpers ──────────────────────────────────────────────

    private function applyPeriod($query, string $period, string $column = 'created_at')
    {
        match ($period) {
            'today' => $query->whereDate($column, now()->toDateString()),
            'week'  => $query->where($column, '>=', now()->startOfWeek(Carbon::MONDAY)),
            'month' => $query->where($column, '>=', now()->startOfMonth()),
            default => null,
        };
    }

    private function applyPeriodDate($query, string $period, string $column = 'created_at')
    {
        match ($period) {
            'today' => $query->where($column, now()->toDateString()),
            'week'  => $query->where($column, '>=', now()->startOfWeek(Carbon::MONDAY)->toDateString()),
            'month' => $query->where($column, '>=', now()->startOfMonth()->toDateString()),
            default => null,
        };
    }
}
