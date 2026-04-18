<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permet les ventes "hors stock" (services, informations, etc.)
 * - stock_item_id devient nullable
 * - ad_hoc_label stocke le libelle de la vente quand il n'y a pas de stock_item
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Drop the existing FK so we can alter the column
            $table->dropForeign(['stock_item_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_item_id')->nullable()->change();
            $table->string('ad_hoc_label', 150)->nullable()->after('stock_item_id');

            // Re-add FK with nullOnDelete
            $table->foreign('stock_item_id')
                  ->references('id')->on('stock_items')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['stock_item_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('ad_hoc_label');
            $table->unsignedBigInteger('stock_item_id')->nullable(false)->change();

            $table->foreign('stock_item_id')
                  ->references('id')->on('stock_items')
                  ->cascadeOnDelete();
        });
    }
};
