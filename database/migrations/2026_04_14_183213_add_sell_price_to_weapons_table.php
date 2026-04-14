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
        Schema::table('weapons', function (Blueprint $table) {
            $table->unsignedInteger('sell_price')->nullable()->after('craft_time_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('weapons', function (Blueprint $table) {
            $table->dropColumn('sell_price');
        });
    }
};
