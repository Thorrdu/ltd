<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Enterprise;
use App\Models\Product;
use Illuminate\Database\Seeder;

class EnterpriseSeeder extends Seeder
{
    // enterprise_names (separated = distinct), [[product_name, sell_price, purchase_price, usual_price], ...]
    private const ENTERPRISES = [
        [['AutoV', 'Garage Moto'], [
            ['Jerrican d\'essence',  180,  50, 180],
            ['Kit de nettoyage',      50,  20,  50],
        ]],
        [['Jupiter'], [
            ['Bouteille vide',   4,   1,   3],
            ['Canette vide',     4,   1,   3],
            ['Sucre',            4,   1,   3],
            ['Bidon vide',     150,  75, 200],
        ]],
        [['Vigneron'], [
            ['Bouteille de vin vide', 30, 15, 35],
            ['Ciseaux',               70, 20, 70],
        ]],
        [['Bahamas', 'Unicorn', 'Tequilala'], [
            ['Citron vert',  6,  1,  6],
            ['Citron',       5,  1,  5],
            ['Blue Bull',   15,  1,  3],
        ]],
        [['Quikly'], [
            ['Tasse vide',           3, 1, 2],
            ['Tasse vide (papier)',  3, 1, 2],
        ]],
        [['Mystere'], [
            ['Spray pesticide', 5000, 1500, 5000],
        ]],
    ];

    public function run(): void
    {
        $enterpriseCategory = Category::firstOrCreate(
            ['name' => 'FOURNITURES ENTREPRISE'],
            ['column' => 'left', 'sort_order' => 99]
        );

        $sortCounter = 0;

        foreach (self::ENTERPRISES as [$names, $rawProducts]) {
            $products = $this->resolveProducts($rawProducts, $enterpriseCategory);

            foreach ($names as $name) {
                $enterprise = Enterprise::create([
                    'name' => $name,
                    'sort_order' => $sortCounter++,
                ]);

                foreach ($products as $pIndex => $item) {
                    $enterprise->products()->attach($item['product']->id, [
                        'price' => $item['sell_price'],
                        'sort_order' => $pIndex,
                    ]);
                }
            }
        }
    }

    private function resolveProducts(array $rawProducts, Category $enterpriseCategory): array
    {
        $resolved = [];

        foreach ($rawProducts as $pIndex => [$name, $sellPrice, $purchasePrice, $usualPrice]) {
            $key = mb_strtolower($name);
            $existing = Product::whereRaw('LOWER(name) = ?', [$key])->first();

            if ($existing) {
                $existing->update(['is_enterprise' => true]);
                if ($purchasePrice !== null && $existing->purchase_price === null) {
                    $existing->update(['purchase_price' => $purchasePrice]);
                }
                if ($usualPrice !== null && $existing->usual_price === null) {
                    $existing->update(['usual_price' => $usualPrice]);
                }
                $product = $existing;
            } else {
                $product = Product::create([
                    'category_id' => $enterpriseCategory->id,
                    'name' => $name,
                    'purchase_price' => $purchasePrice,
                    'usual_price' => $usualPrice,
                    'price' => $sellPrice,
                    'is_retail' => false,
                    'is_enterprise' => true,
                    'sort_order' => $pIndex,
                ]);
            }

            $resolved[] = [
                'product' => $product,
                'sell_price' => $sellPrice,
            ];
        }

        return $resolved;
    }
}
