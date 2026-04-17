<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    public const CATEGORIES = [
        'weapon_finished' => 'Armes',
        'weapon_plan'     => 'Plans',
        'weapon_piece'    => 'Pieces armurerie',
        'raw_material'    => 'Matieres premieres',
        'ammo'            => 'Munitions',
        'melee'           => 'Armes blanches',
        'drug'            => 'Drogues',
        'drug_raw'        => 'Drogues (matieres premieres)',
        'farm_consumable' => 'Consommables agricoles',
        'tool'            => 'Outils',
        'electronic'      => 'Electronique',
        'argent'          => 'Argent',
        'misc'            => 'Divers',
    ];

    public const CATEGORY_COLORS = [
        'weapon_finished' => 'success',
        'weapon_plan'     => 'warning',
        'weapon_piece'    => 'info',
        'raw_material'    => 'gray',
        'ammo'            => 'info',
        'melee'           => 'warning',
        'drug'            => 'danger',
        'drug_raw'        => 'gray',
        'farm_consumable' => 'gray',
        'tool'            => 'gray',
        'electronic'      => 'gray',
        'argent'          => 'warning',
        'misc'            => 'gray',
    ];

    protected $fillable = [
        'category', 'slug', 'name',
        'weapon_id',
        'quantity',
        'unit_weight_g',
        'default_sell_price', 'price_min', 'price_max', 'default_purchase_price',
        'is_sellable', 'is_active',
        'sort_order', 'notes',
    ];

    protected $casts = [
        'is_sellable' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSellable($query)
    {
        return $query->where('is_sellable', true);
    }

    public function scopeOfCategory($query, string $cat)
    {
        return $query->where('category', $cat);
    }

    public function addQuantity(int $qty): void
    {
        $this->increment('quantity', $qty);
    }

    public function removeQuantity(int $qty): void
    {
        $this->decrement('quantity', $qty);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
