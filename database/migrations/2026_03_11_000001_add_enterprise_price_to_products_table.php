<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('enterprise_price')->nullable()->after('price');
        });

        // Make pivot price nullable (null = use product's default enterprise_price)
        Schema::table('enterprise_product', function (Blueprint $table) {
            $table->integer('price')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('enterprise_price');
        });

        Schema::table('enterprise_product', function (Blueprint $table) {
            $table->integer('price')->default(0)->change();
        });
    }
};
