<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeaponStockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'weapon_stock_id', 'quantity_change', 'reason', 'unit_cost',
        'weapon_contract_id', 'user_id', 'attributed_to_user_id',
        'notes', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public const REASONS = [
        'purchase' => 'Achat',
        'gather' => 'Récolte',
        'craft_consume' => 'Craft (consommé)',
        'craft_produce' => 'Craft (produit)',
        'sale' => 'Vente',
        'delivery' => 'Livraison contrat',
        'adjustment' => 'Ajustement',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(WeaponStock::class, 'weapon_stock_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(WeaponContract::class, 'weapon_contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attributedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attributed_to_user_id');
    }
}
