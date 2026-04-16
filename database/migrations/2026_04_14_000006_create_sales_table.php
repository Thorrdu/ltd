<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table UNIQUE des ventes : chaque vente est obligatoirement liee a un `stock_item`
 * (qui identifie l'article vendu et sa categorie : weapon_finished, ammo, melee, drug...).
 * Un contrat peut etre associe optionnellement via `weapon_contract_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('total_price');
            $table->string('buyer_name', 100);
            $table->foreignId('sold_by_user_id')->constrained('users');
            $table->foreignId('weapon_contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['sold_by_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
