<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McRequest extends Model
{
    public const CATEGORIES = [
        'amende'          => 'Amende',
        'entretien_moto'  => 'Entretien moto',
        'equipement'      => 'Equipement',
        'medical'         => 'Frais medicaux',
        'autre'           => 'Autre',
    ];

    public const STATUSES = [
        'pending'   => 'En attente',
        'approved'  => 'Approuvee',
        'rejected'  => 'Refusee',
        'cancelled' => 'Annulee',
    ];

    protected $fillable = [
        'user_id', 'category', 'amount', 'description', 'photo_path', 'status',
        'handled_by_user_id', 'handled_at', 'handler_notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'integer',
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
