<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public $timestamps = false;

    public const REASONS = [
        'purchase'      => 'Achat',
        'gather'        => 'Recolte',
        'craft_consume' => 'Craft (consomme)',
        'craft_produce' => 'Craft (produit)',
        'sale'          => 'Vente',
        'delivery'      => 'Livraison contrat',
        'attribution'   => 'Attribution membre',
        'adjustment'    => 'Ajustement',
    ];

    protected $fillable = [
        'stock_item_id', 'quantity_change', 'reason', 'unit_cost',
        'weapon_contract_id', 'user_id', 'attributed_to_user_id',
        'notes', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
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

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
