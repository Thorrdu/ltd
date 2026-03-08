<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Enterprise extends Model
{
    protected $fillable = ['name', 'notes', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'enterprise_product')
            ->withPivot('price', 'sort_order')
            ->orderByPivot('sort_order');
    }
}
