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
        Schema::table('users', function (Blueprint $table) {
            $table->string('sim_pin', 100)->nullable()->after('role');
        });

        // Set default PIN "1234" for existing users
        \App\Models\User::query()->update(['sim_pin' => bcrypt('1234')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sim_pin');
        });
    }
};
