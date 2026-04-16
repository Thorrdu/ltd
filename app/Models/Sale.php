<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    public const TYPES = [
        'weapon' => 'Arme',
        'ammo' => 'Munition',
        'drug' => 'Drogue',
        'melee' => 'Arme blanche',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'item_type', 'item_id', 'stock_item_id', 'item_name',
        'quantity', 'unit_price', 'total_price',
        'buyer_name', 'sold_by_user_id', 'validated_by_user_id', 'validated_at',
        'notes',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by_user_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class, 'item_id');
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->item_type] ?? $this->item_type;
    }
}
