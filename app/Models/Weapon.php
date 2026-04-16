<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Weapon extends Model
{
    protected $fillable = [
        'name', 'slug', 'craft_time_seconds', 'sell_price',
        'reference_purchase_price', 'price_min', 'price_max',
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

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public function contractItems(): HasMany
    {
        return $this->hasMany(WeaponContractItem::class);
    }

    public function getRecipeAttribute(): array
    {
        return [
            'plans'    => $this->recipe_plans,
            'ressort'  => $this->recipe_ressort,
            'canon'    => $this->recipe_canon,
            'poignee'  => $this->recipe_poignee,
            'corp'     => $this->recipe_corp,
            'metal'    => $this->recipe_metal,
            'polymere' => $this->recipe_polymere,
        ];
    }

    public function planStockItem(): ?StockItem
    {
        return StockItem::where('slug', 'plan_' . $this->slug)->first();
    }

    public function finishedStockItem(): ?StockItem
    {
        return StockItem::where('slug', 'weapon_' . $this->slug)->first();
    }
}
