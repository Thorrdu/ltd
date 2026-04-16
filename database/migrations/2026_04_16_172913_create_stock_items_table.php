<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('category', 30)->index();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->unsignedInteger('unit_weight_g')->nullable();
            $table->unsignedInteger('default_sell_price')->nullable();
            $table->unsignedInteger('default_purchase_price')->nullable();
            $table->foreignId('weapon_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('quantity_external')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['category', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
