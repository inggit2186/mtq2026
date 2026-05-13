<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            $table->json('participant_maqra_lot_ranges')->nullable()->after('participant_maqra_lot_max');
        });
    }

    public function down(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            $table->dropColumn('participant_maqra_lot_ranges');
        });
    }
};
