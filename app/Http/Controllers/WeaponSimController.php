<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponContract;
use App\Models\WeaponContractItem;
use App\Models\WeaponSale;
use App\Models\WeaponStock;
use App\Models\WeaponStockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class WeaponSimController extends Controller
{
    public function hub()
    {
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('mc-hub', [
            'members' => $members,
        ]);
    }

    public function index()
    {
        $weapons = Weapon::active()->orderBy('sort_order')->get();
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('simulateur-armes', [
            'weaponsJson' => $weapons->toJson(),
            'members' => $members,
        ]);
    }

    public function munitions()
    {
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('simulateur-munitions', [
            'members' => $members,
        ]);
    }

    public function espaceMembres()
    {
        $weapons = Weapon::active()->orderBy('sort_order')->get();
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('espace-membres', [
            'weaponsJson' => $weapons->toJson(),
            'membersJson' => $members->toJson(),
            'members' => $members,
        ]);
    }

    // ── Auth helpers ────────────────────────────────────────

    private function authUser(Request $request): ?User
    {
        $userId = $request->header('X-Sim-User');

        return $userId ? User::find($userId) : null;
    }

    private function requireAuth(Request $request): ?JsonResponse
    {
        return $this->authUser($request) ? null : response()->json(['error' => 'unauthorized'], 403);
    }

    public function login(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'pin' => 'required|string',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $user = User::findOrFail($request->user_id);

        if (! $user->sim_pin) {
            return response()->json(['error' => 'Aucun PIN configuré. Contactez un officier.'], 400);
        }

        if (! $user->checkSimPin($request->pin)) {
            return response()->json(['error' => 'PIN incorrect'], 403);
        }

        return response()->json([
            'ok' => true,
            'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
        ]);
    }

    // ── Data API ────────────────────────────────────────────

    public function apiData(Request $request): JsonResponse
    {
        if ($denied = $this->requireAuth($request)) {
            return $denied;
        }

        $contracts = WeaponContract::with(['items.weapon', 'createdBy'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($c) => $this->mapContract($c));

        $allContracts = WeaponContract::with(['items.weapon', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($c) => $this->mapContract($c));

        $stock = WeaponStock::orderBy('category')->orderBy('sort_order')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'category' => $s->category,
                'name' => $s->name,
                'slug' => $s->slug,
                'quantity' => $s->quantity,
                'weapon_id' => $s->weapon_id,
            ]);

        $movements = WeaponStockMovement::with(['stock', 'user', 'attributedTo', 'contract'])
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get()
            ->map(fn ($m) => [
                'stock_name' => $m->stock->name ?? '?',
                'quantity_change' => $m->quantity_change,
                'reason' => $m->reason,
                'reason_label' => WeaponStockMovement::REASONS[$m->reason] ?? $m->reason,
                'unit_cost' => $m->unit_cost,
                'contract' => $m->contract->name ?? null,
                'user' => $m->user->name ?? '?',
                'attributed_to' => $m->attributedTo->name ?? null,
                'notes' => $m->notes,
                'date' => $m->created_at?->format('d/m H:i'),
            ]);

        $sales = WeaponSale::with(['weapon', 'user', 'soldBy', 'contract'])
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get()
            ->map(fn ($s) => [
                'weapon' => $s->weapon->name ?? '?',
                'quantity' => $s->quantity,
                'unit_price' => $s->unit_price,
                'total' => $s->total,
                'buyer' => $s->buyer_name,
                'contract' => $s->contract->name ?? null,
                'user' => $s->user->name ?? '?',
                'sold_by' => $s->soldBy->name ?? null,
                'notes' => $s->notes,
                'date' => $s->created_at?->format('d/m H:i'),
            ]);

        $totalRevenue = WeaponSale::selectRaw('SUM(quantity * unit_price) as total')->value('total') ?? 0;

        $lowStock = WeaponStock::where('quantity', '<=', 2)
            ->whereIn('category', ['piece', 'plan', 'raw_material'])
            ->get(['name', 'quantity', 'category']);

        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return response()->json([
            'contracts' => $contracts,
            'all_contracts' => $allContracts,
            'stock' => $stock,
            'movements' => $movements,
            'sales' => $sales,
            'finance' => ['total_revenue' => $totalRevenue],
            'alerts' => $lowStock,
            'reasons' => WeaponStockMovement::REASONS,
            'contract_statuses' => WeaponContract::STATUSES,
            'members' => $members,
        ]);
    }

    private function mapContract(WeaponContract $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'client' => $c->client_name,
            'status' => $c->status,
            'status_label' => WeaponContract::STATUSES[$c->status] ?? $c->status,
            'notes' => $c->notes,
            'created_by' => $c->createdBy->name ?? '?',
            'progress' => $c->progress,
            'items' => $c->items->map(fn ($i) => [
                'id' => $i->id,
                'weapon' => $i->weapon->name ?? '?',
                'weapon_id' => $i->weapon_id,
                'weapon_slug' => $i->weapon->slug ?? '?',
                'qty_ordered' => $i->qty_ordered,
                'qty_delivered' => $i->qty_delivered,
                'remaining' => $i->remaining,
            ]),
        ];
    }

    // ── Sales ───────────────────────────────────────────────

    public function createSale(Request $request): JsonResponse
    {
        if ($denied = $this->requireAuth($request)) {
            return $denied;
        }

        $user = $this->authUser($request);

        $v = Validator::make($request->all(), [
            'weapon_id' => 'required|exists:weapons,id',
            'quantity' => 'required|integer|min:1|max:99',
            'unit_price' => 'required|numeric|min:0',
            'buyer_name' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $weapon = Weapon::findOrFail($request->weapon_id);
        $qty = (int) $request->quantity;

        $weaponStock = WeaponStock::where('slug', 'weapon_' . $weapon->slug)->first();
        $warning = null;
        if (! $weaponStock || $weaponStock->quantity < $qty) {
            $have = $weaponStock->quantity ?? 0;
            $warning = "⚠ Stock insuffisant ({$have} en stock). Le stock passe en négatif.";
        }

        if ($weaponStock) {
            $weaponStock->decrement('quantity', $qty);
            WeaponStockMovement::create([
                'weapon_stock_id' => $weaponStock->id,
                'quantity_change' => -$qty,
                'reason' => 'sale',
                'user_id' => $user->id,
                'notes' => 'Vente: ' . $qty . '× ' . $weapon->name . ' → ' . $request->buyer_name,
                'created_at' => now(),
            ]);
        }

        WeaponSale::create([
            'weapon_id' => $request->weapon_id,
            'quantity' => $qty,
            'unit_price' => $request->unit_price,
            'buyer_name' => $request->buyer_name,
            'user_id' => $user->id,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'ok' => true,
            'message' => $qty . '× ' . $weapon->name . ' vendu(s) à ' . $request->buyer_name,
            'warning' => $warning,
        ]);
    }

    // ── Movements ───────────────────────────────────────────

    public function createMovement(Request $request): JsonResponse
    {
        if ($denied = $this->requireAuth($request)) {
            return $denied;
        }

        $user = $this->authUser($request);

        $v = Validator::make($request->all(), [
            'weapon_stock_id' => 'required|exists:weapon_stocks,id',
            'quantity_change' => 'required|integer|not_in:0',
            'reason' => 'required|string|in:' . implode(',', array_keys(WeaponStockMovement::REASONS)),
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $stock = WeaponStock::findOrFail($request->weapon_stock_id);
        $change = (int) $request->quantity_change;

        if ($change < 0 && $stock->quantity < abs($change)) {
            return response()->json(['error' => 'Stock insuffisant (' . $stock->quantity . ' en stock)'], 400);
        }

        $stock->increment('quantity', $change);

        WeaponStockMovement::create([
            'weapon_stock_id' => $stock->id,
            'quantity_change' => $change,
            'reason' => $request->reason,
            'unit_cost' => $request->reason === 'purchase' ? $request->unit_cost : null,
            'user_id' => $user->id,
            'notes' => $request->notes,
            'created_at' => now(),
        ]);

        $action = $change > 0 ? 'ajouté' : 'retiré';

        return response()->json(['ok' => true, 'message' => abs($change) . '× ' . $stock->name . ' ' . $action]);
    }

    // ── Contracts ───────────────────────────────────────────

    public function createContract(Request $request): JsonResponse
    {
        if ($denied = $this->requireAuth($request)) {
            return $denied;
        }

        $user = $this->authUser($request);

        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'client_name' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.weapon_id' => 'required|exists:weapons,id',
            'items.*.qty_ordered' => 'required|integer|min:1|max:999',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $contract = WeaponContract::create([
            'name' => $request->name,
            'client_name' => $request->client_name,
            'status' => 'pending',
            'notes' => $request->notes,
            'created_by_user_id' => $user->id,
        ]);

        foreach ($request->items as $item) {
            WeaponContractItem::create([
                'weapon_contract_id' => $contract->id,
                'weapon_id' => $item['weapon_id'],
                'qty_ordered' => $item['qty_ordered'],
                'qty_delivered' => 0,
            ]);
        }

        return response()->json(['ok' => true, 'message' => 'Contrat "' . $contract->name . '" créé']);
    }

    public function updateContract(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAuth($request)) {
            return $denied;
        }

        $user = $this->authUser($request);
        if (! $user->isOfficer()) {
            return response()->json(['error' => 'Réservé aux officiers'], 403);
        }

        $contract = WeaponContract::findOrFail($id);

        $v = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:' . implode(',', array_keys(WeaponContract::STATUSES)),
            'notes' => 'sometimes|nullable|string|max:500',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        if ($request->has('status')) {
            $contract->status = $request->status;
        }
        if ($request->has('notes')) {
            $contract->notes = $request->notes;
        }
        $contract->save();

        return response()->json(['ok' => true, 'message' => 'Contrat mis à jour']);
    }

    public function updateContractItem(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAuth($request)) {
            return $denied;
        }

        $item = WeaponContractItem::findOrFail($id);

        $v = Validator::make($request->all(), [
            'qty_delivered' => 'sometimes|integer|min:0|max:999',
            'qty_ordered' => 'sometimes|integer|min:1|max:999',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        if ($request->has('qty_delivered')) {
            $item->qty_delivered = min($request->qty_delivered, $item->qty_ordered);
        }
        if ($request->has('qty_ordered')) {
            $user = $this->authUser($request);
            if (! $user->isOfficer()) {
                return response()->json(['error' => 'Réservé aux officiers'], 403);
            }
            $item->qty_ordered = $request->qty_ordered;
        }
        $item->save();

        return response()->json(['ok' => true, 'message' => 'Contrat mis à jour']);
    }

    // ── Members (officers only) ─────────────────────────────

    public function createMember(Request $request): JsonResponse
    {
        if ($denied = $this->requireAuth($request)) {
            return $denied;
        }

        $user = $this->authUser($request);
        if (! $user->isOfficer()) {
            return response()->json(['error' => 'Réservé aux officiers'], 403);
        }

        $allowedRoles = array_keys(User::ROLES);
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'role' => 'required|string|in:' . implode(',', $allowedRoles),
            'pin' => 'required|string|min:4|max:20',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        $slug = strtolower(str_replace(' ', '.', $request->name));

        $member = User::create([
            'name' => $request->name,
            'email' => $slug . '@lost.mc',
            'password' => Hash::make('lost-' . $slug),
            'role' => $request->role,
            'sim_pin' => Hash::make($request->pin),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Membre "' . $member->name . '" créé',
            'member' => ['id' => $member->id, 'name' => $member->name, 'role' => $member->role],
        ]);
    }

    public function updateMember(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAuth($request)) {
            return $denied;
        }

        $user = $this->authUser($request);
        if (! $user->isOfficer()) {
            return response()->json(['error' => 'Réservé aux officiers'], 403);
        }

        $member = User::findOrFail($id);

        $allowedRoles = array_keys(User::ROLES);
        $v = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'role' => 'sometimes|string|in:' . implode(',', $allowedRoles),
            'pin' => 'sometimes|string|min:4|max:20',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        if ($request->has('name')) {
            $member->name = $request->name;
        }
        if ($request->has('role')) {
            $member->role = $request->role;
        }
        if ($request->has('pin')) {
            $member->sim_pin = Hash::make($request->pin);
        }
        $member->save();

        return response()->json(['ok' => true, 'message' => 'Membre "' . $member->name . '" mis à jour']);
    }

    public function changePin(Request $request): JsonResponse
    {
        if ($denied = $this->requireAuth($request)) {
            return $denied;
        }

        $user = $this->authUser($request);

        $v = Validator::make($request->all(), [
            'current_pin' => 'required|string',
            'new_pin' => 'required|string|min:4|max:20',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $v->errors()], 422);
        }

        if (! $user->checkSimPin($request->current_pin)) {
            return response()->json(['error' => 'PIN actuel incorrect'], 403);
        }

        $user->sim_pin = Hash::make($request->new_pin);
        $user->save();

        return response()->json(['ok' => true, 'message' => 'PIN modifié']);
    }
}
