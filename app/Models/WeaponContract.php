<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeaponContract extends Model
{
    protected $fillable = ['name', 'client_name', 'status', 'notes', 'created_by_user_id'];

    public const STATUSES = [
        'pending' => 'En attente',
        'in_progress' => 'En cours',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(WeaponContractItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getProgressAttribute(): float
    {
        $items = $this->items;
        if ($items->isEmpty()) {
            return 0;
        }
        $total = $items->sum('qty_ordered');
        $done = $items->sum('qty_delivered');

        return $total > 0 ? round(($done / $total) * 100, 1) : 0;
    }
}
