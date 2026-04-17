<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'label', 'type', 'value', 'description', 'sort_order'];

    public const TYPES = [
        'integer' => 'Nombre entier',
        'float'   => 'Nombre décimal',
        'string'  => 'Texte',
        'boolean' => 'Oui / Non',
        'json'    => 'JSON',
    ];

    public const GROUPS = [
        'raw_materials'  => 'Matières premières',
        'pieces'         => 'Pièces intermédiaires',
        'weapon_recipes' => 'Recettes armes',
        'ammo_recipes'   => 'Recettes munitions',
        'sell_prices'    => 'Prix de vente',
        'multipliers'    => 'Multiplicateurs',
        'drugs'          => 'Drogues',
        'melee_weapons'  => 'Armes blanches',
        'cotisations'    => 'Cotisations',
        'rankings'       => 'Classements',
        'general'        => 'Général',
    ];

    private const CACHE_KEY = 'app_settings';
    private const CACHE_TTL = 3600;

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::pluck('value', 'key')->all();
        });

        $raw = $settings[$key] ?? null;

        if ($raw === null) {
            return $default;
        }

        $setting = self::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        return self::castValue($raw, $setting->type);
    }

    public static function set(string $key, mixed $value): void
    {
        self::where('key', $key)->update(['value' => (string) $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public static function getGroup(string $group): array
    {
        return self::where('group', $group)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->key => self::castValue($s->value, $s->type)])
            ->all();
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'float'   => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }
}
