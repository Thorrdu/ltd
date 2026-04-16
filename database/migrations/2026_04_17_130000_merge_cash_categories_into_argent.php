<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bases deja migrees avec cash_dirty / cash_clean : convergence vers `argent`.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('stock_items')->whereIn('category', ['cash_dirty', 'cash_clean'])->update(['category' => 'argent']);
    }

    public function down(): void
    {
        DB::table('stock_items')->where('slug', 'misc_argent_sale')->update(['category' => 'cash_dirty']);
        DB::table('stock_items')->where('slug', 'cash_argent_propre')->update(['category' => 'cash_clean']);
    }
};
