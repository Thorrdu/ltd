<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeaponSale extends Model
{
    protected $fillable = [
        'weapon_id', 'weapon_contract_id', 'quantity', 'unit_price',
        'buyer_name', 'user_id', 'sold_by_user_id', 'notes',
    ];

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(WeaponContract::class, 'weapon_contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by_user_id');
    }

    public function getTotalAttribute(): int
    {
        return $this->quantity * $this->unit_price;
    }
}
