<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('score_entries', function (Blueprint $table): void {
            // Add new JSON column for all judge scores in one row (new format)
            // This replaces the need for multiple rows per participant per round
            $table->json('scores')->nullable()->after('judging_round');

            // Add average_score column (cached for quick access)
            // This avoids needing to aggregate multiple rows for display
            $table->decimal('average_score', 5, 2)->nullable()->after('scores');

            // Add index for efficient queries by participant + round
            $table->index(['participant_id', 'judging_round'], 'idx_participant_round');
        });
    }

    public function down(): void
    {
        Schema::table('score_entries', function (Blueprint $table): void {
            $table->dropIndex('idx_participant_round');
            $table->dropColumn(['scores', 'average_score']);
        });
    }
};