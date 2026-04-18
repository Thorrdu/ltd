<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->boolean('is_quick_sale')->default(false)->after('is_sellable')
                ->comment('Apparait dans la vente rapide (armes, munitions, drogues, armes blanches)');
        });

        // Flag existing items that should appear in quick sale
        DB::table('stock_items')
            ->whereIn('category', ['weapon_finished', 'ammo', 'drug', 'melee'])
            ->update(['is_quick_sale' => true]);
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropColumn('is_quick_sale');
        });
    }
};
