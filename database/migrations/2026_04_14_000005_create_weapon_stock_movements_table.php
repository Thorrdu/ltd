<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weapon_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weapon_stock_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_change');
            $table->string('reason'); // purchase, gather, craft_consume, craft_produce, sale, delivery, adjustment
            $table->foreignId('weapon_contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('attributed_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weapon_stock_movements');
    }
};
