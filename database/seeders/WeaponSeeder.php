<?php

namespace Database\Seeders;

use App\Models\Weapon;
use Illuminate\Database\Seeder;

/**
 * Seed the weapons catalog (recipe metadata + reference prices).
 * Stock entries for each weapon (plans, finished) are seeded by StockItemSeeder.
 */
class WeaponSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Prix de vente reference : dans les bandes min-max du tableau RP armes.
         */
        $weapons = [
            ['name' => 'SNS',             'slug' => 'sns',     'craft_time_seconds' => 15, 'sell_price' => 60000,   'price_min' => 35000,  'price_max' => 70000,   'reference_purchase_price' => 30000, 'recipe_plans' => 1, 'recipe_ressort' => 1, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 5,  'recipe_polymere' => 5,  'sort_order' => 1],
            ['name' => 'WN 29 Pistol',    'slug' => 'wn29',    'craft_time_seconds' => 15, 'sell_price' => 80000,   'price_min' => 45000,  'price_max' => 90000,   'reference_purchase_price' => null,  'recipe_plans' => 1, 'recipe_ressort' => 1, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 10, 'recipe_polymere' => 5,  'sort_order' => 2],
            ['name' => 'Ceramic Pistol',  'slug' => 'ceramic', 'craft_time_seconds' => 15, 'sell_price' => 70000,   'price_min' => 40000,  'price_max' => 80000,   'reference_purchase_price' => null,  'recipe_plans' => 1, 'recipe_ressort' => 1, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 5,  'recipe_polymere' => 5,  'sort_order' => 3],
            ['name' => 'Pistol',          'slug' => 'pistol',  'craft_time_seconds' => 15, 'sell_price' => 100000,  'price_min' => 60000,  'price_max' => 120000,  'reference_purchase_price' => null,  'recipe_plans' => 1, 'recipe_ressort' => 1, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 5,  'recipe_polymere' => 10, 'sort_order' => 4],
            ['name' => 'Heavy Pistol',    'slug' => 'heavy',   'craft_time_seconds' => 25, 'sell_price' => 160000,  'price_min' => 90000,  'price_max' => 180000,  'reference_purchase_price' => null,  'recipe_plans' => 1, 'recipe_ressort' => 2, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 10, 'recipe_polymere' => 10, 'sort_order' => 5],
            ['name' => 'Cal .50',         'slug' => 'cal50',   'craft_time_seconds' => 30, 'sell_price' => 200000,  'price_min' => 110000, 'price_max' => 220000,  'reference_purchase_price' => null,  'recipe_plans' => 1, 'recipe_ressort' => 2, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 10, 'recipe_polymere' => 15, 'sort_order' => 6],
            ['name' => 'AK-47',           'slug' => 'ak47',    'craft_time_seconds' => 60, 'sell_price' => 1000000, 'price_min' => 800000, 'price_max' => 1200000, 'reference_purchase_price' => null,  'recipe_plans' => 1, 'recipe_ressort' => 5, 'recipe_canon' => 1, 'recipe_poignee' => 1, 'recipe_corp' => 1, 'recipe_metal' => 40, 'recipe_polymere' => 50, 'sort_order' => 7],
        ];

        foreach ($weapons as $w) {
            Weapon::updateOrCreate(['slug' => $w['slug']], $w);
        }
    }
}
