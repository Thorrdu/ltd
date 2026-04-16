<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = [
        'stock_item_id',
        'quantity', 'unit_price', 'total_price',
        'buyer_name',
        'sold_by_user_id', 'weapon_contract_id',
        'validated_by_user_id', 'validated_at',
        'notes',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by_user_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(WeaponContract::class, 'weapon_contract_id');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeInPeriod($query, string $period)
    {
        return match ($period) {
            'today' => $query->whereDate('created_at', now()->toDateString()),
            'week'  => $query->where('created_at', '>=', now()->startOfWeek()),
            'month' => $query->where('created_at', '>=', now()->startOfMonth()),
            default => $query,
        };
    }
}
