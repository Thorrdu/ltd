<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CategoryProductSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(base_path('_backup/data/produits.json'));
        $categories = json_decode($json, true);

        foreach ($categories as $index => $cat) {
            $category = Category::create([
                'name' => $cat['name'],
                'column' => $cat['column'] ?? 'left',
                'sort_order' => $index,
            ]);

            foreach ($cat['products'] as $pIndex => $prod) {
                Product::create([
                    'category_id' => $category->id,
                    'name' => $prod['name'],
                    'price' => (int) str_replace([' ', "\u{A0}"], '', $prod['price']),
                    'is_retail' => true,
                    'is_enterprise' => false,
                    'sort_order' => $pIndex,
                ]);
            }
        }
    }
}
