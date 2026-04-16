<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Argent : une seule categorie `argent` (articles distincts propre / sale).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('stock_items')->where('slug', 'misc_argent_sale')->update(['category' => 'argent']);

        $exists = DB::table('stock_items')->where('slug', 'cash_argent_propre')->exists();
        if (! $exists) {
            DB::table('stock_items')->insert([
                'category'               => 'argent',
                'slug'                   => 'cash_argent_propre',
                'name'                   => 'Argent propre',
                'weapon_id'              => null,
                'quantity'               => 0,
                'unit_weight_g'          => null,
                'default_sell_price'     => 1,
                'default_purchase_price' => null,
                'is_sellable'            => false,
                'is_active'              => true,
                'sort_order'             => 2,
                'notes'                  => 'Montant en $, non vendable directement',
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('stock_items')->where('slug', 'misc_argent_sale')->update(['category' => 'misc']);
        DB::table('stock_items')->where('slug', 'cash_argent_propre')->delete();
    }
};
