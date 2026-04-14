<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weapons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('craft_time_seconds')->nullable();
            $table->unsignedTinyInteger('recipe_plans')->default(1);
            $table->unsignedTinyInteger('recipe_ressort')->default(0);
            $table->unsignedTinyInteger('recipe_canon')->default(0);
            $table->unsignedTinyInteger('recipe_poignee')->default(0);
            $table->unsignedTinyInteger('recipe_corp')->default(0);
            $table->unsignedTinyInteger('recipe_metal')->default(0);
            $table->unsignedTinyInteger('recipe_polymere')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weapons');
    }
};
