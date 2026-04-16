<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_access_rules', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 80)->unique();
            $table->string('label', 120);
            $table->string('min_role', 32);
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index('min_role');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_access_rules');
    }
};
