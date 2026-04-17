<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajouter les colonnes price_min et price_max
        Schema::table('stock_items', function (Blueprint $table) {
            $table->unsignedInteger('price_min')->nullable()->after('default_sell_price');
            $table->unsignedInteger('price_max')->nullable()->after('price_min');
        });

        // 2. Mettre à jour les prix des drogues selon le tableau indicatif
        // default_sell_price = Prix PNJ haut, price_min = Prix PNJ bas, price_max = Prix PNJ haut
        $drugs = [
            // slug => [price_min (PNJ bas), price_max (PNJ haut), default_sell_price (= PNJ haut)]
            'drug_weed_bluedream'  => ['price_min' => 140,  'price_max' => 180,  'default_sell_price' => 180],
            'drug_weed_whitewidow' => ['price_min' => 80,   'price_max' => 140,  'default_sell_price' => 140],
            'drug_weed_purple'     => ['price_min' => 50,   'price_max' => 80,   'default_sell_price' => 80],
            'drug_weed_ogkush'     => ['price_min' => 30,   'price_max' => 50,   'default_sell_price' => 50],
            'drug_cook'            => ['price_min' => 350,  'price_max' => 750,  'default_sell_price' => 750],
            'drug_amph_basse'      => ['price_min' => 500,  'price_max' => 500,  'default_sell_price' => 500],
            'drug_amph_moyen'      => ['price_min' => 900,  'price_max' => 900,  'default_sell_price' => 900],
            'drug_amph_haute'      => ['price_min' => 1000, 'price_max' => 1000, 'default_sell_price' => 1000],
            'drug_meth_basse'      => ['price_min' => 600,  'price_max' => 750,  'default_sell_price' => 750],
            'drug_meth_moyen'      => ['price_min' => 1000, 'price_max' => 1500, 'default_sell_price' => 1500],
            'drug_meth_haute'      => ['price_min' => 2000, 'price_max' => 2600, 'default_sell_price' => 2600],
            'drug_lsd'             => ['price_min' => 3800, 'price_max' => 3800, 'default_sell_price' => 3800],
            'drug_mdma'            => ['price_min' => 2900, 'price_max' => 2900, 'default_sell_price' => 2900],
            'drug_lean'            => ['price_min' => 2400, 'price_max' => 2400, 'default_sell_price' => 2400],
        ];

        foreach ($drugs as $slug => $prices) {
            DB::table('stock_items')
                ->where('slug', $slug)
                ->update($prices);
        }
    }

    public function down(): void
    {
        // Restaurer les anciens prix (prix au sac / vente orga)
        $drugs = [
            'drug_weed_bluedream'  => 100,
            'drug_weed_whitewidow' => 65,
            'drug_weed_purple'     => 45,
            'drug_weed_ogkush'     => 30,
            'drug_cook'            => 325,
            'drug_amph_basse'      => 400,
            'drug_amph_moyen'      => 750,
            'drug_amph_haute'      => 850,
            'drug_meth_basse'      => 550,
            'drug_meth_moyen'      => 900,
            'drug_meth_haute'      => 1350,
            'drug_lsd'             => 3250,
            'drug_mdma'            => 2250,
            'drug_lean'            => 1800,
        ];

        foreach ($drugs as $slug => $price) {
            DB::table('stock_items')
                ->where('slug', $slug)
                ->update([
                    'default_sell_price' => $price,
                    'price_min' => null,
                    'price_max' => null,
                ]);
        }

        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropColumn(['price_min', 'price_max']);
        });
    }
};
