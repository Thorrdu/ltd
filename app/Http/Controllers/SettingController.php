<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private const PAGE_KEY = 'parametres';

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
        return view('parametres');
    }

    /**
     * GET /parametres/api/list — all settings grouped
     */
    public function apiList(Request $request): JsonResponse
    {
        if ($deny = $this->requireAccess($request)) {
            return $deny;
        }

        $settings = Setting::orderBy('group')->orderBy('sort_order')->get();

        $grouped = [];
        foreach ($settings as $s) {
            $grouped[$s->group][] = [
                'id'          => $s->id,
                'key'         => $s->key,
                'label'       => $s->label,
                'type'        => $s->type,
                'value'       => $s->value,
                'description' => $s->description,
            ];
        }

        return response()->json([
            'groups'      => Setting::GROUPS,
            'types'       => Setting::TYPES,
            'settings'    => $grouped,
        ]);
    }

    /**
     * PUT /parametres/api/{id} — update a single setting value
     */
    public function apiUpdate(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->requireAccess($request)) {
            return $deny;
        }

        $setting = Setting::find($id);
        if (! $setting) {
            return response()->json(['error' => 'Paramètre introuvable'], 404);
        }

        $request->validate([
            'value' => 'required|string|max:1000',
        ]);

        $setting->update(['value' => $request->input('value')]);
        Setting::clearCache();

        return response()->json(['ok' => true, 'message' => 'Paramètre mis à jour']);
    }
}
