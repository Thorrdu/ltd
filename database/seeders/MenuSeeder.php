<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Product;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    private const FRUIT_NAMES = ['Pomme', 'Banane', 'Poire', 'Orange'];

    public function run(): void
    {
        $json = file_get_contents(base_path('_backup/data/menus.json'));
        $menus = json_decode($json, true);

        foreach ($menus as $index => $entry) {
            $menu = Menu::create([
                'type' => $entry['type'],
                'name' => $entry['name'] ?? null,
                'price' => isset($entry['price'])
                    ? (int) str_replace([' ', "\u{A0}"], '', $entry['price'])
                    : null,
                'promo_text' => $entry['text'] ?? null,
                'sort_order' => $index,
            ]);

            if ($entry['type'] === 'menu' && isset($entry['items'])) {
                foreach ($entry['items'] as $sortOrder => $itemName) {
                    if (str_contains(mb_strtolower($itemName), 'fruit au choix')) {
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
