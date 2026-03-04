<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\EnterpriseGroup;
use App\Models\Product;
use Illuminate\Database\Seeder;

class EnterpriseSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(base_path('_backup/data/entreprises.json'));
        $groups = json_decode($json, true);

        $enterpriseCategory = Category::firstOrCreate(
            ['name' => 'FOURNITURES ENTREPRISE'],
            ['column' => 'left', 'sort_order' => 99]
        );

        foreach ($groups as $gIndex => $group) {
            $eg = EnterpriseGroup::create([
                'name' => $group['name'],
                'sort_order' => $gIndex,
            ]);

            foreach ($group['products'] as $pIndex => $prod) {
                $price = (int) str_replace([' ', "\u{A0}"], '', $prod['price']);

                $existing = Product::whereRaw('LOWER(name) = ?', [mb_strtolower($prod['name'])])->first();

                if ($existing) {
                    $existing->update(['is_enterprise' => true]);
                    $product = $existing;
                } else {
                    $product = Product::create([
                        'category_id' => $enterpriseCategory->id,
                        'name' => $prod['name'],
                        'price' => $price,
                        'is_retail' => false,
                        'is_enterprise' => true,
                        'sort_order' => $pIndex,
                    ]);
                }

                $eg->products()->attach($product->id, [
                    'price' => $price,
                    'sort_order' => $pIndex,
                ]);
            }
        }
    }
}
