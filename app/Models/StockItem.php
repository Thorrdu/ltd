<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    /**
     * Master list of categories (used by /ventes select, stocks, filters...).
     * Keep in sync with the Phase 3 taxonomy (cf. plan-developpement.md annexe A).
     */
    public const CATEGORIES = [
        'weapon_finished'  => 'Armes',
        'ammo'             => 'Munitions',
        'melee'            => 'Armes blanches',
        'drug'             => 'Drogues (produits finis)',
        'drug_raw'         => 'Drogues (matieres premieres)',
        'farm_consumable'  => 'Consommables agricoles',
        'piece'            => 'Pieces armurerie',
        'raw_material'     => 'Matieres premieres',
        'plan'             => 'Plans',
        'tool'             => 'Outils',
        'electronic'       => 'Electronique',
        'misc'             => 'Divers',
    ];

    /**
     * Mapping StockItem.category -> Sale.item_type (simplified typology for sales reports).
     */
    public const CATEGORY_TO_SALE_TYPE = [
        'weapon_finished' => 'weapon',
        'ammo'            => 'ammo',
        'melee'           => 'melee',
        'drug'            => 'drug',
        'drug_raw'        => 'drug',
    ];

    protected $fillable = [
        'category', 'name', 'slug',
        'unit_weight_g',
        'default_sell_price', 'default_purchase_price',
        'weapon_id',
        'quantity_in_stock', 'quantity_external',
        'is_active', 'sort_order',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfCategory($query, string $cat)
    {
        return $query->where('category', $cat);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getSaleTypeAttribute(): string
    {
        return self::CATEGORY_TO_SALE_TYPE[$this->category] ?? 'other';
    }
}
