<?php

namespace App\Http\Controllers;

use App\Models\McNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private function authUser(Request $request): ?User
    {
        $uid = $request->header('X-Sim-User');
        if (!$uid) return null;
        return User::where('id', $uid)->where('is_active', true)->first();
    }

    /**
     * GET /notifications/api/list  —  last 50 notifications for the connected user
     */
    public function apiList(Request $request): JsonResponse
    {
        $user = $this->authUser($request);
        if (!$user) return response()->json(['error' => 'Non connecte'], 401);

        $notifs = McNotification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($n) => [
                'id'      => $n->id,
                'type'    => $n->type,
                'title'   => $n->title,
                'body'    => $n->body,
                'link'    => $n->link,
                'read'    => $n->isRead(),
                'date'    => $n->created_at->format('d/m H:i'),
                'ago'     => $n->created_at->diffForHumans(),
            ]);

        $unread = McNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['notifications' => $notifs, 'unread' => $unread]);
    }

    /**
     * POST /notifications/api/read  —  mark notifications as read
     * Body: { ids: [1,2,3] } or { all: true }
     */
    public function apiMarkRead(Request $request): JsonResponse
    {
        $user = $this->authUser($request);
        if (!$user) return response()->json(['error' => 'Non connecte'], 401);

        $query = McNotification::where('user_id', $user->id)->whereNull('read_at');

        if ($request->input('all')) {
            $query->update(['read_at' => now()]);
        } else {
            $ids = $request->input('ids', []);
            if (!is_array($ids)) return response()->json(['error' => 'ids invalides'], 422);
            $query->whereIn('id', $ids)->update(['read_at' => now()]);
        }

        $unread = McNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['ok' => true, 'unread' => $unread]);
    }

    /**
     * GET /notifications/api/count  —  just the unread count (lightweight polling)
     */
    public function apiCount(Request $request): JsonResponse
    {
        $user = $this->authUser($request);
        if (!$user) return response()->json(['unread' => 0]);

        $unread = McNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['unread' => $unread]);
    }
}
