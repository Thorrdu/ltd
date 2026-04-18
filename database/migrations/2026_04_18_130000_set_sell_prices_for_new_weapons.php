<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les nouvelles armes (Micro SMG, Mini SMG, Tec 9, AKU, Pompe) sont achetees
 * aux orga — leur prix d'achat EST aussi leur prix de vente de reference.
 * On met a jour sell_price, price_min, price_max sur weapons
 * et default_sell_price sur stock_items.
 */
return new class extends Migration
{
    public function up(): void
    {
        // [slug => [sell_price, price_min, price_max]]
        // sell_price = reference_purchase_price (prix achat orga)
        // price_min / price_max = fourchette de vente RP
        $updates = [
            'micro_smg' => ['sell' => 300000, 'min' => 210000, 'max' => 420000],
            'mini_smg'  => ['sell' => 300000, 'min' => 190000, 'max' => 380000],
            'tec9'      => ['sell' => 325000, 'min' => 190000, 'max' => 380000],
            'aku'       => ['sell' => 500000, 'min' => 450000, 'max' => 900000],
            'pompe'     => ['sell' => 600000, 'min' => 450000, 'max' => 900000],
            'ak47'      => ['sell' => 800000, 'min' => 800000, 'max' => 1200000],
        ];

        foreach ($updates as $slug => $prices) {
            DB::table('weapons')->where('slug', $slug)->update([
                'sell_price' => $prices['sell'],
                'price_min'  => $prices['min'],
                'price_max'  => $prices['max'],
            ]);

            DB::table('stock_items')->where('slug', 'weapon_' . $slug)->update([
                'default_sell_price' => $prices['sell'],
                'price_min'          => $prices['min'],
                'price_max'          => $prices['max'],
            ]);
        }
    }

    public function down(): void
    {
        $slugs = ['micro_smg', 'mini_smg', 'tec9', 'aku', 'pompe', 'ak47'];

        foreach ($slugs as $slug) {
            DB::table('weapons')->where('slug', $slug)->update([
                'sell_price' => null,
                'price_min'  => null,
                'price_max'  => null,
            ]);

            DB::table('stock_items')->where('slug', 'weapon_' . $slug)->update([
                'default_sell_price' => null,
                'price_min'          => null,
                'price_max'          => null,
            ]);
        }
    }
};
