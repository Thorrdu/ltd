<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WeaponSimController extends Controller
{
    private const FILE = 'weapon-sim.json';
    private const PASSWORD = 'lost2026';

    private function authorize(Request $request): bool
    {
        return $request->header('X-Sim-Password') === self::PASSWORD;
    }

    private function readData(): array
    {
        if (Storage::exists(self::FILE)) {
            $data = json_decode(Storage::get(self::FILE), true);
            if (is_array($data)) {
                return $data;
            }
        }

        return ['contracts' => [], 'stock' => $this->defaultStock()];
    }

    private function writeData(array $data): void
    {
        Storage::put(self::FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function defaultStock(): array
    {
        return [
            'plans_wn29' => 0,
            'plans_ceramic' => 0,
            'plans_pistol' => 0,
            'plans_heavy' => 0,
            'plans_cal50' => 0,
            'ressort' => 0,
            'canon' => 0,
            'poignee' => 0,
            'corp' => 0,
            'metal' => 0,
            'polymere' => 0,
            'minerai' => 0,
            'petrole' => 0,
        ];
    }

    public function getData(Request $request): JsonResponse
    {
        if (! $this->authorize($request)) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        return response()->json($this->readData());
    }

    public function saveData(Request $request): JsonResponse
    {
        if (! $this->authorize($request)) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'contracts' => 'present|array',
            'contracts.*.id' => 'required|string',
            'contracts.*.name' => 'required|string|max:100',
            'contracts.*.weapons' => 'required|array',
            'contracts.*.weapons.*.key' => 'required|string',
            'contracts.*.weapons.*.qty' => 'required|integer|min:1|max:999',
            'contracts.*.done' => 'present|array',
            'stock' => 'present|array',
        ]);

        $stock = $this->defaultStock();
        foreach ($stock as $key => $_) {
            if (isset($validated['stock'][$key])) {
                $stock[$key] = max(0, (int) $validated['stock'][$key]);
            }
        }
        $validated['stock'] = $stock;

        $this->writeData($validated);

        return response()->json(['ok' => true]);
    }
}
