<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weapon_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // raw_material, piece, plan, finished_weapon
            $table->foreignId('weapon_id')->nullable()->constrained('weapons')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('quantity')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weapon_stocks');
    }
};
