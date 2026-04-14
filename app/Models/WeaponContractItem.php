<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeaponContractItem extends Model
{
    protected $fillable = ['weapon_contract_id', 'weapon_id', 'qty_ordered', 'qty_delivered'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(WeaponContract::class, 'weapon_contract_id');
    }

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class);
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->qty_ordered - $this->qty_delivered);
    }
}
