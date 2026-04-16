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
}
