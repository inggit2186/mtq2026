<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, add the column if it doesn't exist
        Schema::table('mfq_results', function (Blueprint $table) {
            if (!Schema::hasColumn('mfq_results', 'district_id')) {
                $table->unsignedBigInteger('district_id')->nullable();
            }
        });

        // Update existing rows: set district_id from participants table
        DB::statement("
            UPDATE mfq_results r
            INNER JOIN participants p ON p.id = r.participant_id
            SET r.district_id = p.district_id
            WHERE r.district_id = 0 OR r.district_id IS NULL
        ");

        // Set any remaining district_id = 0 to the minimum valid district id (fallback)
        // In production this shouldn't happen, but just in case
        $minDistrictId = DB::table('districts')->min('id');
        if ($minDistrictId) {
            DB::table('mfq_results')
                ->where(function ($q) {
                    $q->where('district_id', 0)->orWhereNull('district_id');
                })
                ->update(['district_id' => $minDistrictId]);
        }

        // Now add FK constraint, defaults, and indices
        Schema::table('mfq_results', function (Blueprint $table) use ($minDistrictId) {
            // Change column to non-nullable with default
            $table->unsignedBigInteger('district_id')
                ->nullable(false)
                ->default($minDistrictId ?? 1)
                ->change();

            // Add FK
            $table->foreign('district_id')
                ->references('id')
                ->on('districts')
                ->onDelete('cascade');

            // Add index
            $table->index(['mfq_session_id', 'district_id']);

            // Add unique constraint (1 row per district per session)
            $table->unique(['mfq_session_id', 'district_id'], 'mfq_results_session_district_unique');
        });
    }

    public function down(): void
    {
        Schema::table('mfq_results', function (Blueprint $table): void {
            $table->dropForeign(['district_id']);
            $table->dropIndex(['mfq_session_id', 'district_id']);
        });
    }
};
