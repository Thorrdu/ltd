<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'purchase_price', 'usual_price', 'price',
        'is_retail', 'is_enterprise', 'sort_order',
    ];

    protected $casts = [
        'purchase_price' => 'integer',
        'usual_price' => 'integer',
        'price' => 'integer',
        'is_retail' => 'boolean',
        'is_enterprise' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menu_product')
            ->withPivot('choice_group', 'sort_order');
    }

    public function enterprises(): BelongsToMany
    {
        return $this->belongsToMany(Enterprise::class, 'enterprise_product')
            ->withPivot('price', 'sort_order');
    }

    public function scopeRetail($query)
    {
        return $query->where('is_retail', true);
    }

    public function scopeEnterprise($query)
    {
        return $query->where('is_enterprise', true);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' \u{20AC}';
    }

    public function getFormattedPurchasePriceAttribute(): string
    {
        return $this->purchase_price !== null
            ? number_format($this->purchase_price, 0, ',', ' ') . ' \u{20AC}'
            : '';
    }

    public function getFormattedUsualPriceAttribute(): string
    {
        return $this->usual_price !== null
            ? number_format($this->usual_price, 0, ',', ' ') . ' \u{20AC}'
            : '';
    }
}
