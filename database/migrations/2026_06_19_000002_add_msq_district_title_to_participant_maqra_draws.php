<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participant_maqra_draws', function (Blueprint $table): void {
            $table->foreignId('msq_district_title_id')
                ->nullable()
                ->after('maqra_package_id')
                ->constrained('msq_district_titles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('participant_maqra_draws', function (Blueprint $table): void {
            $table->dropForeign(['msq_district_title_id']);
            $table->dropColumn('msq_district_title_id');
        });
    }
};
