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
     * Access resolution: if a rule exists for the given key, the user must reach the min_role level.
     * If no rule exists, access is denied by default (secure by default).
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
        if (! $rule) {
            return false;
        }

        return $user->isAtLeast($rule->min_role);
    }

    public function getMinRoleLabelAttribute(): string
    {
        return User::ROLES[$this->min_role]['label'] ?? $this->min_role;
    }
}
