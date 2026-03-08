<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CategoryProductSeeder extends Seeder
{
    // [name, column, [[product_name, sell_price, purchase_price, usual_price], ...]]
    private const CATEGORIES = [
        ['SNACKS', 'left', [
            ['Sandwich',            300, 100, null],
            ['Gaufre de BXL',       200,  50, null],
            ['Chips',               200,  50, null],
            ['Barres de cereales',  150,  40, null],
            ['Barre de chocolat',   150,  40, null],
            ['Glace au chocolat',   200,  75, null],
            ['Glace a la vanille',  200,  75, null],
            ['Glace a la fraise',   200,  75, null],
            ['Pomme',                80,  20, null],
            ['Banane',               80,  20, null],
            ['Poire',                80,  20, null],
            ['Orange',               80,  20, null],
        ]],
        ['BOISSONS', 'right', [
            ['Cafe',                       150,  35,  45],
            ['Bouteille d\'eau',           200,  50, 100],
            ['Blue Bull',                  200,   1, null],
            ['Jupiter',                    300, 100, null],
            ['M27',                        300, 110, null],
            ['Solaris',                    400, 110, null],
            ['Vin rouge',                  500, 210, null],
            ['Vin blanc',                  500, 220, null],
            ['Champagne',                 1000, 500, null],
            ['Vodka',                      750,  15, 500],
            ['Rhum',                       750,  15, 500],
            ['Whisky',                     750,  35, 500],
            ['Super Vodka (Challenge)',   1000,  35,  60],
        ]],
        ['COIN FESTIF', 'left', [
            ['Bombe a graffiti',              500, 100, null],
            ['Feu d\'artifice (flare)',       400, 100, null],
            ['Fusee de feux d\'artifice',    2000, 750, null],
            ['Pyro petit',                    500, 100, null],
            ['Pyro moyen',                   1000, 250, null],
            ['Pyro pirate',                   600, 150, null],
            ['Pyro tapis',                   1200, 400, null],
            ['Pyro tapis long',              2000, 750, null],
            ['Pyro flare 1',                 2000,  50, null],
            ['Pyro flare 2',                 2000, 750, null],
            ['Pyro flare 3',                 2000, 750, null],
            ['Fontaine de feux d\'artifices',1000, 250, null],
        ]],
        ['OBJETS DU QUOTIDIEN', 'right', [
            ['Ticket a gratter',     100,  75, null],
            ['Radio',                200,  40, 100],
            ['Telephone',           1000, 200, 600],
            ['Tablette',            1750, 500, 1500],
            ['Sac de tenue',         250,  50, 200],
            ['Portefeuille',         100,  20, 100],
            ['Jerrican d\'essence',  200,  50, 180],
            ['Paquet de cigarettes',1200, 450, 1000],
            ['Cigarette (unite)',     50,  10, null],
            ['Feuille a rouler',       3,   1,    1],
            ['Skateboard',          1500, 200, null],
            ['Rollers',             2000, 500, null],
            ['Parachute',           6000, 1500, null],
        ]],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $catIndex => [$catName, $column, $products]) {
            $category = Category::create([
                'name' => $catName,
                'column' => $column,
                'sort_order' => $catIndex,
            ]);

            foreach ($products as $pIndex => [$name, $price, $purchasePrice, $usualPrice]) {
                Product::create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'price' => $price,
                    'purchase_price' => $purchasePrice,
                    'usual_price' => $usualPrice,
                    'is_retail' => true,
                    'is_enterprise' => false,
                    'sort_order' => $pIndex,
                ]);
            }
        }
    }
}
