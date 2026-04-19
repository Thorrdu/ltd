<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\McNotification;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CotisationController extends Controller
{
    private const PAGE_KEY = 'cotisations';

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

        return view('cotisations', [
            'members' => $members,
        ]);
    }

    /**
     * Get the cotisation amount for a given role.
     */
    private function amountForRole(string $role): int
    {
        $level = User::ROLES[$role]['level'] ?? 0;
        if ($level >= 3) {
            return (int) Setting::get('cotisation_officer', 10000);
        }
        if ($level >= 2) {
            return (int) Setting::get('cotisation_member', 5000);
        }

        return (int) Setting::get('cotisation_prospect', 2000);
    }

    /**
     * Get the Monday (period_start) for a given date.
     */
    private function weekStart(Carbon $date = null): Carbon
    {
        return ($date ?? now())->copy()->startOfWeek(Carbon::MONDAY);
    }

    /**
     * Ensure cotisation rows exist for the current week for all active members.
     * Called on list/generation.
     */
    private function ensureCurrentWeek(): void
    {
        $start = $this->weekStart();
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

        $members = User::where('is_active', true)->get();

        foreach ($members as $member) {
            Cotisation::firstOrCreate(
                ['user_id' => $member->id, 'period_start' => $start->toDateString()],
                [
                    'period_end'  => $end->toDateString(),
                    'amount_due'  => $this->amountForRole($member->role),
                    'amount_paid' => 0,
                ]
            );
        }
    }

    // ── API : List ──────────────────────────────────────────

    public function apiList(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user = $this->authUser($request);
        $scope = $request->query('scope', 'current'); // current | history
        $weekOffset = (int) $request->query('week_offset', 0); // 0 = this week, -1 = last week

        // Ensure current week cotisations exist
        $this->ensureCurrentWeek();

        $weekStart = $this->weekStart()->addWeeks($weekOffset);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        if ($scope === 'current') {
            $cotisations = Cotisation::with(['user', 'markedBy'])
                ->where('period_start', $weekStart->toDateString())
                ->orderBy('user_id')
                ->get();

            $data = $cotisations->map(function (Cotisation $c) {
                return [
                    'id'           => $c->id,
                    'user_id'      => $c->user_id,
                    'user_name'    => $c->user?->name ?? 'Inconnu',
                    'user_role'    => $c->user?->role ?? '',
                    'role_label'   => User::ROLES[$c->user?->role ?? '']['label'] ?? '',
                    'period_start' => $c->period_start->format('d/m/Y'),
                    'period_end'   => $c->period_end->format('d/m/Y'),
                    'amount_due'   => $c->amount_due,
                    'amount_paid'  => $c->amount_paid,
                    'is_paid'      => $c->isPaid(),
                    'is_partial'   => $c->isPartial(),
                    'remaining'    => $c->remaining(),
                    'paid_at'      => $c->paid_at?->format('d/m/Y H:i'),
                    'marked_by'    => $c->markedBy?->name,
                    'notes'        => $c->notes,
                ];
            });

            $stats = [
                'total_due'  => $cotisations->sum('amount_due'),
                'total_paid' => $cotisations->sum('amount_paid'),
                'paid_count' => $cotisations->filter(fn ($c) => $c->isPaid())->count(),
                'total_count' => $cotisations->count(),
                'week_label' => $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m/Y'),
            ];

            return response()->json([
                'cotisations' => $data,
                'stats'       => $stats,
                'week_offset' => $weekOffset,
                'user_role'   => $user->role,
                'is_officer'  => $user->isOfficer(),
            ]);
        }

        // History: my cotisations
        $query = Cotisation::with(['user', 'markedBy'])
            ->orderByDesc('period_start');

        if (! $user->isOfficer()) {
            $query->where('user_id', $user->id);
        }

        $memberId = $request->query('member_id');
        if ($memberId && $user->isOfficer()) {
            $query->where('user_id', (int) $memberId);
        }

        $cotisations = $query->limit(100)->get();

        $data = $cotisations->map(function (Cotisation $c) {
            return [
                'id'           => $c->id,
                'user_id'      => $c->user_id,
                'user_name'    => $c->user?->name ?? 'Inconnu',
                'user_role'    => $c->user?->role ?? '',
                'role_label'   => User::ROLES[$c->user?->role ?? '']['label'] ?? '',
                'period_start' => $c->period_start->format('d/m/Y'),
                'period_end'   => $c->period_end->format('d/m/Y'),
                'amount_due'   => $c->amount_due,
                'amount_paid'  => $c->amount_paid,
                'is_paid'      => $c->isPaid(),
                'is_partial'   => $c->isPartial(),
                'remaining'    => $c->remaining(),
                'paid_at'      => $c->paid_at?->format('d/m/Y H:i'),
                'marked_by'    => $c->markedBy?->name,
                'notes'        => $c->notes,
            ];
        });

        return response()->json([
            'cotisations' => $data,
            'user_role'   => $user->role,
            'is_officer'  => $user->isOfficer(),
        ]);
    }

    // ── API : Mark paid ──────────────────────────────────────

    public function apiMarkPaid(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user = $this->authUser($request);
        if (! $user->isOfficer()) {
            return response()->json(['error' => 'Officier minimum requis'], 403);
        }

        $cotisation = Cotisation::find($id);
        if (! $cotisation) {
            return response()->json(['error' => 'Cotisation introuvable'], 404);
        }

        $amount = $request->input('amount');
        if ($amount === null || $amount === '') {
            $amount = $cotisation->amount_due;
        } else {
            $amount = (int) $amount;
        }

        if ($amount < 0) {
            return response()->json(['error' => 'Montant invalide'], 422);
        }

        $cotisation->update([
            'amount_paid'      => $amount,
            'paid_at'          => now(),
            'marked_by_user_id' => $user->id,
            'notes'            => $request->input('notes', $cotisation->notes),
        ]);

        $label = $amount >= $cotisation->amount_due ? 'Cotisation marquée payée' : 'Montant enregistré';

        // Notify the member
        if ($cotisation->user_id !== $user->id) {
            McNotification::notify(
                $cotisation->user_id,
                'cotisation',
                $amount >= $cotisation->amount_due
                    ? 'Cotisation marquée payée'
                    : 'Paiement partiel enregistré (' . number_format($amount, 0, ',', ' ') . ' $)',
                'Par ' . $user->name,
                '/cotisations'
            );
        }

        return response()->json([
            'ok'      => true,
            'message' => $label . '.',
        ]);
    }

    // ── API : Generate week ──────────────────────────────────

    public function apiGenerate(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user = $this->authUser($request);
        if (! $user->isOfficer()) {
            return response()->json(['error' => 'Officier minimum requis'], 403);
        }

        $this->ensureCurrentWeek();

        return response()->json([
            'ok'      => true,
            'message' => 'Cotisations de la semaine générées.',
        ]);
    }

    // ── API : My status (for alerts) ────────────────────────

    public function apiMyStatus(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user = $this->authUser($request);
        $start = $this->weekStart();

        $cotisation = Cotisation::where('user_id', $user->id)
            ->where('period_start', $start->toDateString())
            ->first();

        $overdue = Cotisation::where('user_id', $user->id)
            ->where('amount_paid', '<', \DB::raw('amount_due'))
            ->where('period_start', '<', $start->toDateString())
            ->count();

        return response()->json([
            'current_week' => $cotisation ? [
                'amount_due'  => $cotisation->amount_due,
                'amount_paid' => $cotisation->amount_paid,
                'is_paid'     => $cotisation->isPaid(),
                'remaining'   => $cotisation->remaining(),
            ] : null,
            'overdue_count' => $overdue,
        ]);
    }
}
