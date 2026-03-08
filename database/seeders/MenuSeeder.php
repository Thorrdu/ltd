<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Product;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    private const FRUIT_NAMES = ['Pomme', 'Banane', 'Poire', 'Orange'];

    // [type, name|null, price|null, promo_text|null, items[]|null]
    private const MENUS = [
        ['menu', 'Menu Midi', 400, null, ['Sandwich', 'Blue Bull', 'Fruit au choix']],
        ['menu', 'Menu LTD', 500, null, ['Sandwich', 'Blue Bull', 'Gaufre de BXL', 'Cafe']],
        ['promo', null, null, '10 menus achetes = 1 menu offert', null],
        ['menu', 'Pack Nouvel Arrivant', 1200, null, ['Telephone', 'Radio', 'Gaufre de BXL', 'Blue Bull']],
    ];

    public function run(): void
    {
        foreach (self::MENUS as $index => [$type, $name, $price, $promoText, $items]) {
            $menu = Menu::create([
                'type' => $type,
                'name' => $name,
                'price' => $price,
                'promo_text' => $promoText,
                'sort_order' => $index,
            ]);

            if ($type === 'menu' && $items !== null) {
                foreach ($items as $sortOrder => $itemName) {
                    if (mb_strtolower($itemName) === 'fruit au choix') {
                        $this->attachFruits($menu, $sortOrder);
                    } else {
                        $product = Product::whereRaw('LOWER(name) = ?', [mb_strtolower($itemName)])->first();
                        if ($product) {
                            $menu->products()->attach($product->id, [
                                'choice_group' => null,
                                'sort_order' => $sortOrder,
                            ]);
                        }
                    }
                }
            }
        }
    }

    private function attachFruits(Menu $menu, int $sortOrder): void
    {
        foreach (self::FRUIT_NAMES as $fruitName) {
            $product = Product::whereRaw('LOWER(name) = ?', [mb_strtolower($fruitName)])->first();
            if ($product) {
                $menu->products()->attach($product->id, [
                    'choice_group' => 'fruit',
                    'sort_order' => $sortOrder,
                ]);
            }
        }
    }
}
