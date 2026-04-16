<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 additive columns for stock_movements:
 *  - reconciliation tracking for attribution movements (Phase 3.3).
 *  - treasurer approval workflow for above-threshold attributions (Phase 3.4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->timestamp('reconciled_at')->nullable()->after('notes');
            $table->foreignId('reconciled_by_movement_id')
                ->nullable()
                ->after('reconciled_at')
                ->constrained('stock_movements')
                ->nullOnDelete();

            $table->boolean('requires_approval')->default(false)->after('reconciled_by_movement_id');
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->after('requires_approval')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            $table->index(['attributed_to_user_id', 'reconciled_at']);
            $table->index(['requires_approval', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['attributed_to_user_id', 'reconciled_at']);
            $table->dropIndex(['requires_approval', 'approved_at']);
            $table->dropForeign(['reconciled_by_movement_id']);
            $table->dropForeign(['approved_by_user_id']);
            $table->dropColumn([
                'reconciled_at',
                'reconciled_by_movement_id',
                'requires_approval',
                'approved_by_user_id',
                'approved_at',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
