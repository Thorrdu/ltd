<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = ['category_id', 'name', 'price', 'is_retail', 'is_enterprise', 'sort_order'];

    protected $casts = [
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

    public function enterpriseGroups(): BelongsToMany
    {
        return $this->belongsToMany(EnterpriseGroup::class, 'enterprise_group_product')
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
        return number_format($this->price, 0, ',', ' ') . ' €';
    }
}
