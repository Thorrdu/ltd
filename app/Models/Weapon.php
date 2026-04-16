<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Weapon extends Model
{
    protected $fillable = [
        'name', 'slug', 'craft_time_seconds', 'sell_price', 'reference_purchase_price',
        'recipe_plans', 'recipe_ressort', 'recipe_canon', 'recipe_poignee',
        'recipe_corp', 'recipe_metal', 'recipe_polymere',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WeaponStock::class);
    }

    public function contractItems(): HasMany
    {
        return $this->hasMany(WeaponContractItem::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(WeaponSale::class);
    }

    public function getRecipeAttribute(): array
    {
        return [
            'plans' => $this->recipe_plans,
            'ressort' => $this->recipe_ressort,
            'canon' => $this->recipe_canon,
            'poignee' => $this->recipe_poignee,
            'corp' => $this->recipe_corp,
            'metal' => $this->recipe_metal,
            'polymere' => $this->recipe_polymere,
        ];
    }

    public function planStock(): ?WeaponStock
    {
        return WeaponStock::where('slug', 'plan_' . $this->slug)->first();
    }

    public function finishedStock(): ?WeaponStock
    {
        return WeaponStock::where('slug', 'weapon_' . $this->slug)->first();
    }
}
