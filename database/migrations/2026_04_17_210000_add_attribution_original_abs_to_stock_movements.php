<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quantite initiale d'une attribution (immuable) : les reconciliations partielles
 * mettent a jour quantity_change (reste) sans ecraser l'historique d'affichage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('attribution_original_abs')->nullable()->after('quantity_change');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('attribution_original_abs');
        });
    }
};
