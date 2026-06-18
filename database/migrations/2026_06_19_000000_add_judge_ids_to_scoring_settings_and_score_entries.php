<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scoring_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('scoring_settings', 'penyisihan_judge_ids')) {
                $table->json('penyisihan_judge_ids')->nullable();
            }
            if (! Schema::hasColumn('scoring_settings', 'final_judge_ids')) {
                $table->json('final_judge_ids')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('scoring_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('scoring_settings', 'final_judge_ids')) {
                $table->dropColumn('final_judge_ids');
            }
            if (Schema::hasColumn('scoring_settings', 'penyisihan_judge_ids')) {
                $table->dropColumn('penyisihan_judge_ids');
            }
        });
    }
};
