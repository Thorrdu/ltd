<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeaponStock extends Model
{
    protected $fillable = ['category', 'weapon_id', 'name', 'slug', 'quantity', 'sort_order'];

    public const CATEGORIES = [
        'raw_material' => 'Matière première',
        'piece' => 'Pièce intermédiaire',
        'plan' => 'Plan',
        'finished_weapon' => 'Arme finie',
    ];

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(WeaponStockMovement::class);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function addQuantity(int $qty): void
    {
        $this->increment('quantity', $qty);
    }

    public function removeQuantity(int $qty): void
    {
        $this->decrement('quantity', $qty);
    }
}
