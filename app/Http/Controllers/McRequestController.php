<?php

namespace App\Http\Controllers;

use App\Models\McRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class McRequestController extends Controller
{
    private const PAGE_KEY = 'demandes';

    public function index()
    {
        $members = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('demandes', [
            'members'    => $members,
            'categories' => McRequest::CATEGORIES,
        ]);
    }

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

    // ── API : List ──────────────────────────────────────────

    public function apiList(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user   = $this->authUser($request);
        $scope  = $request->query('scope', 'mine');
        $status = $request->query('status', 'all');

        $query = McRequest::with(['user', 'handledBy']);

        if ($scope === 'mine') {
            $query->where('user_id', $user->id);
        }

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $requests = $query->orderByDesc('created_at')->limit(200)->get();

        $data = $requests->map(function (McRequest $r) {
            return [
                'id'            => $r->id,
                'user_id'       => $r->user_id,
                'user_name'     => $r->user?->name ?? 'Inconnu',
                'category'      => $r->category,
                'category_label' => McRequest::CATEGORIES[$r->category] ?? $r->category,
                'amount'        => $r->amount,
                'description'   => $r->description,
                'photo_url'     => $r->photo_path ? asset('storage/' . $r->photo_path) : null,
                'status'        => $r->status,
                'status_label'  => McRequest::STATUSES[$r->status] ?? $r->status,
                'handler_name'  => $r->handledBy?->name,
                'handler_notes' => $r->handler_notes,
                'handled_at'    => $r->handled_at?->format('d/m/Y H:i'),
                'created_at'    => $r->created_at->format('d/m/Y H:i'),
            ];
        });

        // Stats
        $pending  = McRequest::where('status', 'pending')->count();
        $myPending = McRequest::where('user_id', $user->id)->where('status', 'pending')->count();

        return response()->json([
            'requests'   => $data,
            'stats'      => [
                'pending'    => $pending,
                'my_pending' => $myPending,
            ],
        ]);
    }

    // ── API : Create ────────────────────────────────────────

    public function apiCreate(Request $request): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user = $this->authUser($request);

        $v = Validator::make($request->all(), [
            'category'    => 'required|string|in:' . implode(',', array_keys(McRequest::CATEGORIES)),
            'amount'      => 'required|integer|min:1',
            'description' => 'required|string|max:1000',
            'photo'       => 'nullable|image|max:5120', // 5 MB max
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()->first()], 422);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('demandes', 'public');
        }

        $mcRequest = McRequest::create([
            'user_id'     => $user->id,
            'category'    => $request->input('category'),
            'amount'      => $request->input('amount'),
            'description' => $request->input('description'),
            'photo_path'  => $photoPath,
            'status'      => 'pending',
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Demande soumise avec succes.',
            'id'      => $mcRequest->id,
        ]);
    }

    // ── API : Handle (approve / reject) ─────────────────────

    public function apiHandle(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requireAccess($request)) {
            return $denied;
        }

        $user = $this->authUser($request);
        if (! $user->isAtLeast('treasurer')) {
            return response()->json(['error' => 'Tresorier minimum requis'], 403);
        }

        $mcRequest = McRequest::find($id);
        if (! $mcRequest) {
            return response()->json(['error' => 'Demande introuvable'], 404);
        }
        if (! $mcRequest->isPending()) {
            return response()->json(['error' => 'Cette demande a deja ete traitee'], 422);
        }

        $action = $request->input('action');
        if (! in_array($action, ['approve', 'reject'])) {
            return response()->json(['error' => 'Action invalide'], 422);
        }

        $mcRequest->update([
            'status'             => $action === 'approve' ? 'approved' : 'rejected',
            'handled_by_user_id' => $user->id,
            'handled_at'         => now(),
            'handler_notes'      => $request->input('notes', ''),
        ]);

        $label = $action === 'approve' ? 'approuvee' : 'refusee';

        return response()->json([
            'ok'      => true,
            'message' => 'Demande ' . $label . '.',
        ]);
    }
}
