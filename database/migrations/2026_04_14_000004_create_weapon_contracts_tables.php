<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weapon_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_name');
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();
        });

        Schema::create('weapon_contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weapon_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weapon_id')->constrained();
            $table->unsignedInteger('qty_ordered');
            $table->unsignedInteger('qty_delivered')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weapon_contract_items');
        Schema::dropIfExists('weapon_contracts');
    }
};
