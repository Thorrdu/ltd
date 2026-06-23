<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PageAccessRule extends Model
{
    protected $fillable = [
        'page_key',
        'label',
        'min_role',
        'description',
        'sort_order',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public const CACHE_KEY = 'page_access_rules_all';

    /**
     * Code-level default min_role per page key. Used as a fallback when no DB
     * rule exists for a key, so a non-seeded (or partially seeded) database does
     * not lock out every role except the superadmin. A DB rule, when present,
     * always overrides these defaults.
     *
     * @var array<string, string>
     */
    public const DEFAULT_MIN_ROLES = [
        'panel_admin'          => 'treasurer',
        'panel_armurerie'      => 'officer',
        'mc_hub'               => 'prospect',
        'simulateur_armes'     => 'member',
        'simulateur_munitions' => 'member',
        'espace_membres'       => 'prospect',
        'membres_gestion'      => 'vice_president',
        'matrice_acces'        => 'treasurer',
        'ventes_rapides'       => 'member',
        'stocks_generique'     => 'officer',
        'stocks_validations'   => 'treasurer',
        'stocks_import'        => 'treasurer',
        'comptabilite'         => 'treasurer',
        'classements'          => 'member',
        'demandes'             => 'member',
        'fiches_membres'       => 'officer',
        'cotisations'          => 'prospect',
        'parametres'           => 'treasurer',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Cached map of page_key => rule.
     *
     * @return array<string, self>
     */
    public static function allCached(): array
    {
        return Cache::remember(self::CACHE_KEY, 600, function () {
            return static::orderBy('sort_order')->get()->keyBy('page_key')->all();
        });
    }

    public static function findCached(string $pageKey): ?self
    {
        $rules = self::allCached();

        return $rules[$pageKey] ?? null;
    }

    /**
     * Access resolution: a DB rule (if present) defines the required min_role.
     * If no DB rule exists, a code-level default (DEFAULT_MIN_ROLES) is used so
     * that an un-seeded database does not lock out everyone but the superadmin.
     * If neither exists, access is denied by default (secure by default).
     * Superadmin always has access.
     */
    public static function userCanAccess(?User $user, string $pageKey): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->isSuperadmin()) {
            return true;
        }
        $rule = self::findCached($pageKey);
        $minRole = $rule?->min_role ?? (self::DEFAULT_MIN_ROLES[$pageKey] ?? null);
        if ($minRole === null) {
            return false;
        }

        return $user->isAtLeast($minRole);
    }

    public function getMinRoleLabelAttribute(): string
    {
        return User::ROLES[$this->min_role]['label'] ?? $this->min_role;
    }
}
