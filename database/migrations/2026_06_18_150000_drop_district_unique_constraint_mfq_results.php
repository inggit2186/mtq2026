<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MFQ stores one row per participant (since each participant has their own record)
     * The unique constraint on district_id caused issues when saving multiple participants
     * from the same district in one session.
     */
    public function up(): void
    {
        Schema::table('mfq_results', function (Blueprint $table): void {
            // Drop the district-based unique constraint (keep participant-based one)
            $table->dropUnique('mfq_results_session_district_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mfq_results', function (Blueprint $table): void {
            $table->unique(['mfq_session_id', 'district_id'], 'mfq_results_session_district_unique');
        });
    }
};
