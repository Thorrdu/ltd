<?php

namespace App\Http\Controllers;

use App\Models\PageAccessRule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    // ── Auth helpers ───────────────────────────────────────

    private function authUser(Request $request): ?User
    {
        $userId = $request->header('X-Sim-User');

        return $userId ? User::find($userId) : null;
    }

    private function requireAccess(Request $request, string $pageKey): ?JsonResponse
    {
        $user = $this->authUser($request);
        if (! $user) {
            return response()->json(['error' => 'unauthorized'], 403);
        }
        if (! $user->canAccessPage($pageKey)) {
            return response()->json(['error' => 'Acces refuse pour cette page'], 403);
        }

        return null;
    }

    // ── Views ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = $this->authUser($request);
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('membres', [
            'members' => $members,
            'assignableRoles' => $user ? $user->assignableRoles() : [],
        ]);
    }

    // ── API : members ──────────────────────────────────────

    public function apiList(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request, 'membres_gestion')) {
            return $denied;
        }
        $current = $this->authUser($request);

        $members = User::orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at'])
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'email' => $m->email,
                    'role' => $m->role,
                    'role_label' => User::ROLES[$m->role]['label'] ?? $m->role,
                    'role_level' => User::ROLES[$m->role]['level'] ?? 0,
                    'is_active' => (bool) $m->is_active,
                    'created_at' => $m->created_at?->format('d/m/Y'),
                ];
            });

        return response()->json([
            'members' => $members,
            'roles' => collect(User::ROLES)->map(fn ($v, $k) => ['key' => $k, 'label' => $v['label'], 'level' => $v['level']])->values(),
            'assignable_roles' => $current->assignableRoles(),
            'current_user' => ['id' => $current->id, 'role' => $current->role, 'is_superadmin' => $current->isSuperadmin()],
        ]);
    }

    public function apiCreate(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request, 'membres_gestion')) {
            return $denied;
        }
        $current = $this->authUser($request);

        $allowed = array_column($current->assignableRoles(), 'key');
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'role' => 'required|string|in:' . implode(',', $allowed),
            'pin'  => 'required|string|min:4|max:20',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $slug = Str::slug(Str::lower($request->name), '.');

        $member = User::create([
            'name' => $request->name,
            'email' => $slug . '@lost.mc',
            'password' => Hash::make('lost-' . $slug),
            'role' => $request->role,
            'sim_pin' => Hash::make($request->pin),
            'is_active' => true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Membre "' . $member->name . '" cree',
            'member' => ['id' => $member->id, 'name' => $member->name, 'role' => $member->role],
        ]);
    }

    public function apiUpdate(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAccess($request, 'membres_gestion')) {
            return $denied;
        }
        $current = $this->authUser($request);
        $member = User::findOrFail($id);

        $v = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'role' => 'sometimes|string',
            'pin'  => 'sometimes|string|min:4|max:20',
            'is_active' => 'sometimes|boolean',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        if ($request->has('role')) {
            $newRole = $request->role;
            if (! $current->canAssignRole($newRole)) {
                return response()->json(['error' => 'Vous ne pouvez pas attribuer ce role'], 403);
            }
            // Prevent demoting the only superadmin
            if ($member->isSuperadmin() && $newRole !== User::SUPERADMIN_ROLE) {
                $remaining = User::where('role', User::SUPERADMIN_ROLE)->where('id', '!=', $member->id)->count();
                if ($remaining === 0) {
                    return response()->json(['error' => 'Impossible de retrograder le dernier superadmin'], 400);
                }
            }
            $member->role = $newRole;
        }
        if ($request->has('name')) {
            $member->name = $request->name;
        }
        if ($request->has('pin')) {
            $member->sim_pin = Hash::make($request->pin);
        }
        if ($request->has('is_active')) {
            if ($member->id === $current->id && ! $request->is_active) {
                return response()->json(['error' => 'Vous ne pouvez pas vous desactiver vous-meme'], 400);
            }
            $member->is_active = (bool) $request->is_active;
        }
        $member->save();

        return response()->json(['ok' => true, 'message' => 'Membre "' . $member->name . '" mis a jour']);
    }

    public function apiResetPin(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAccess($request, 'membres_gestion')) {
            return $denied;
        }
        $member = User::findOrFail($id);

        $newPin = (string) random_int(1000, 999999);
        $member->sim_pin = Hash::make($newPin);
        $member->save();

        return response()->json([
            'ok' => true,
            'message' => 'PIN reinitialise pour ' . $member->name,
            'new_pin' => $newPin,
        ]);
    }

    public function apiDelete(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAccess($request, 'membres_gestion')) {
            return $denied;
        }
        $current = $this->authUser($request);
        if (! $current->isSuperadmin()) {
            return response()->json(['error' => 'Suppression reservee au superadmin'], 403);
        }
        $member = User::findOrFail($id);
        if ($member->id === $current->id) {
            return response()->json(['error' => 'Vous ne pouvez pas vous supprimer vous-meme'], 400);
        }
        $member->delete();

        return response()->json(['ok' => true, 'message' => 'Membre supprime']);
    }

    // ── API : access matrix ────────────────────────────────

    public function apiMatrix(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request, 'matrice_acces')) {
            return $denied;
        }

        $rules = PageAccessRule::orderBy('sort_order')->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'page_key' => $r->page_key,
                'label' => $r->label,
                'min_role' => $r->min_role,
                'min_role_label' => User::ROLES[$r->min_role]['label'] ?? $r->min_role,
                'description' => $r->description,
                'sort_order' => $r->sort_order,
                'is_system' => (bool) $r->is_system,
            ];
        });

        return response()->json([
            'rules' => $rules,
            'roles' => collect(User::ROLES)->map(fn ($v, $k) => ['key' => $k, 'label' => $v['label'], 'level' => $v['level']])->values(),
        ]);
    }

    public function apiUpdateMatrix(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAccess($request, 'matrice_acces')) {
            return $denied;
        }

        $rule = PageAccessRule::findOrFail($id);

        $v = Validator::make($request->all(), [
            'min_role' => 'required|string|in:' . implode(',', array_keys(User::ROLES)),
            'description' => 'sometimes|nullable|string|max:255',
        ]);
        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $rule->min_role = $request->min_role;
        if ($request->has('description')) {
            $rule->description = $request->description;
        }
        $rule->save();

        return response()->json(['ok' => true, 'message' => 'Regle mise a jour']);
    }

    // ── Member profile page ────────────────────────────────

    public function profile(Request $request, int $id)
    {
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('membre-profil', [
            'memberId' => $id,
            'members'  => $members,
        ]);
    }

    public function apiProfile(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAccess($request, 'fiches_membres')) {
            return $denied;
        }

        $member = User::find($id);
        if (! $member) {
            return response()->json(['error' => 'Membre introuvable'], 404);
        }

        // Basic info
        $info = [
            'id'         => $member->id,
            'name'       => $member->name,
            'role'       => $member->role,
            'role_label' => User::ROLES[$member->role]['label'] ?? $member->role,
            'is_active'  => (bool) $member->is_active,
            'created_at' => $member->created_at?->format('d/m/Y'),
        ];

        // Open attributions (items in possession)
        $attributions = \App\Models\StockMovement::with(['stockItem'])
            ->openAttribution()
            ->where('attributed_to_user_id', $id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => [
                'id'        => $m->id,
                'item_name' => $m->stockItem?->name ?? 'Inconnu',
                'category'  => $m->stockItem?->category ?? '',
                'quantity'  => abs($m->attribution_original_abs ?? $m->quantity_change),
                'date'      => $m->created_at?->format('d/m/Y'),
            ]);

        // Sales (last 50)
        $sales = \App\Models\Sale::with(['stockItem'])
            ->where('sold_by_user_id', $id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($s) => [
                'id'         => $s->id,
                'item_name'  => $s->stockItem?->name ?? $s->ad_hoc_label ?? 'Article',
                'quantity'   => $s->quantity,
                'total'      => $s->total_price,
                'buyer'      => $s->buyer_name,
                'date'       => $s->created_at->format('d/m/Y H:i'),
            ]);

        $salesTotals = [
            'total_revenue' => (int) \App\Models\Sale::where('sold_by_user_id', $id)->sum('total_price'),
            'total_count'   => \App\Models\Sale::where('sold_by_user_id', $id)->count(),
            'week_revenue'  => (int) \App\Models\Sale::where('sold_by_user_id', $id)->where('created_at', '>=', now()->startOfWeek())->sum('total_price'),
            'month_revenue' => (int) \App\Models\Sale::where('sold_by_user_id', $id)->where('created_at', '>=', now()->startOfMonth())->sum('total_price'),
        ];

        // Stock movements (last 50)
        $movements = \App\Models\StockMovement::with(['stockItem'])
            ->where(function ($q) use ($id) {
                $q->where('user_id', $id)->orWhere('attributed_to_user_id', $id);
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($m) => [
                'id'        => $m->id,
                'item_name' => $m->stockItem?->name ?? 'Inconnu',
                'qty'       => $m->quantity_change,
                'reason'    => $m->reason_label,
                'notes'     => $m->notes,
                'date'      => $m->created_at?->format('d/m/Y H:i'),
            ]);

        // Cotisations (last 20)
        $cotisations = \App\Models\Cotisation::where('user_id', $id)
            ->orderByDesc('period_start')
            ->limit(20)
            ->get()
            ->map(fn ($c) => [
                'id'           => $c->id,
                'period'       => $c->period_start->format('d/m') . ' - ' . $c->period_end->format('d/m/Y'),
                'amount_due'   => $c->amount_due,
                'amount_paid'  => $c->amount_paid,
                'is_paid'      => $c->isPaid(),
                'remaining'    => $c->remaining(),
            ]);

        // Demandes
        $demandes = \App\Models\McRequest::where('user_id', $id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'id'             => $r->id,
                'category_label' => \App\Models\McRequest::CATEGORIES[$r->category] ?? $r->category,
                'amount'         => $r->amount,
                'status'         => $r->status,
                'status_label'   => \App\Models\McRequest::STATUSES[$r->status] ?? $r->status,
                'date'           => $r->created_at->format('d/m/Y'),
            ]);

        return response()->json([
            'info'         => $info,
            'attributions' => $attributions,
            'sales'        => $sales,
            'sales_totals' => $salesTotals,
            'movements'    => $movements,
            'cotisations'  => $cotisations,
            'demandes'     => $demandes,
        ]);
    }
}
