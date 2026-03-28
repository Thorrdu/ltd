<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    protected $fillable = ['type', 'name', 'price', 'promo_price', 'promo_text', 'sort_order'];

    protected $casts = [
        'price' => 'integer',
        'promo_price' => 'integer',
        'sort_order' => 'integer',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'menu_product')
            ->withPivot('choice_group', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function scopeMenus($query)
    {
        return $query->where('type', 'menu');
    }

    public function scopePromos($query)
    {
        return $query->where('type', 'promo');
    }

    public function getDisplayItemsAttribute(): array
    {
        $items = [];
        $choiceGroups = [];

        foreach ($this->products as $product) {
            $group = $product->pivot->choice_group;
            if ($group) {
                if (!isset($choiceGroups[$group])) {
                    $choiceGroups[$group] = true;
                    $items[] = ucfirst($group) . ' au choix';
                }
            } else {
                $items[] = $product->name;
            }
        }

        return $items;
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->price ? number_format($this->price, 0, ',', ' ') . ' €' : '';
    }
}
