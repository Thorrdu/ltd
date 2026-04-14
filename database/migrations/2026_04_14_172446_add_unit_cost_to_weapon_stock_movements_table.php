<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('weapon_stock_movements', function (Blueprint $table) {
            $table->unsignedInteger('unit_cost')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('weapon_stock_movements', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
