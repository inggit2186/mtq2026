<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('official_access_settings', 'participant_maqra_penyisihan_open')) {
                $table->boolean('participant_maqra_penyisihan_open')->default(true)->after('participant_maqra_open');
            }

            if (! Schema::hasColumn('official_access_settings', 'participant_maqra_final_open')) {
                $table->boolean('participant_maqra_final_open')->default(true)->after('participant_maqra_penyisihan_open');
            }
        });
    }

    public function down(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('official_access_settings', 'participant_maqra_final_open')) {
                $table->dropColumn('participant_maqra_final_open');
            }

            if (Schema::hasColumn('official_access_settings', 'participant_maqra_penyisihan_open')) {
                $table->dropColumn('participant_maqra_penyisihan_open');
            }
        });
    }
};
