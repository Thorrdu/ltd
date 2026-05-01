<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cotisation extends Model
{
    protected $fillable = [
        'user_id', 'period_start', 'period_end',
        'amount_due', 'amount_paid', 'is_exempt',
        'paid_at', 'marked_by_user_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end'   => 'date',
            'paid_at'      => 'datetime',
            'amount_due'   => 'integer',
            'amount_paid'  => 'integer',
            'is_exempt'    => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }

    public function isPaid(): bool
    {
        return $this->is_exempt || $this->amount_paid >= $this->amount_due;
    }

    public function isExempt(): bool
    {
        return (bool) $this->is_exempt;
    }

    public function isPartial(): bool
    {
        return $this->amount_paid > 0 && $this->amount_paid < $this->amount_due;
    }

    public function remaining(): int
    {
        return max(0, $this->amount_due - $this->amount_paid);
    }
}
