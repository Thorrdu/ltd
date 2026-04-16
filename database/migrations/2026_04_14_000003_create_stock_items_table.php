<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table de stock UNIQUE pour toute l'application (armes, pieces, plans, matieres,
 * munitions, drogues, armes blanches, etc.).
 *
 * Chaque entree est identifiee par sa `category` (enum libre) et son `slug` unique.
 * Les armes (categories weapon_*) sont reliees a `weapons` via `weapon_id` pour les
 * recettes de craft et les stats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('category', 30)->index();
            $table->string('slug', 120)->unique();
            $table->string('name', 120);
            $table->foreignId('weapon_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity')->default(0);
            $table->unsignedInteger('unit_weight_g')->nullable();
            $table->unsignedInteger('default_sell_price')->nullable();
            $table->unsignedInteger('default_purchase_price')->nullable();
            $table->boolean('is_sellable')->default(true);
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
