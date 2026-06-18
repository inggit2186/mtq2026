<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if unique constraint already exists (added by previous migration)
        $exists = collect(DB::select("SHOW INDEX FROM mfq_results WHERE Key_name = 'mfq_results_session_district_unique'"))->isNotEmpty();
        if ($exists) {
            return;
        }

        Schema::table('mfq_results', function (Blueprint $table): void {
            $table->unique(['mfq_session_id', 'district_id'], 'mfq_results_session_district_unique');
        });
    }

    public function down(): void
    {
        Schema::table('mfq_results', function (Blueprint $table): void {
            $table->dropUnique('mfq_results_session_district_unique');
        });
    }
};
