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
        'reconciled_at', 'reconciled_by_movement_id',
        'requires_approval', 'from_external', 'approved_by_user_id', 'approved_at',
        'rejected_at', 'rejection_reason',
    ];

    protected $casts = [
        'created_at'        => 'datetime',
        'reconciled_at'     => 'datetime',
        'approved_at'       => 'datetime',
        'rejected_at'       => 'datetime',
        'requires_approval' => 'boolean',
        'from_external'     => 'boolean',
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function reconciledByMovement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reconciled_by_movement_id');
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /**
     * Open attribution = attribution reason, not reconciled, not rejected.
     * Approved-but-pending attributions are still considered "open" from the beneficiary's POV.
     */
    public function scopeOpenAttribution($query)
    {
        return $query->where('reason', 'attribution')
            ->whereNull('reconciled_at')
            ->whereNull('rejected_at');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('requires_approval', true)
            ->whereNull('approved_at')
            ->whereNull('rejected_at');
    }

    public function isOpenAttribution(): bool
    {
        return $this->reason === 'attribution'
            && $this->reconciled_at === null
            && $this->rejected_at === null;
    }
}
