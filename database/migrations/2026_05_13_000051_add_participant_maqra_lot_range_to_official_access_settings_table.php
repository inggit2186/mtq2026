<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            $table->unsignedInteger('participant_maqra_lot_min')->nullable()->after('participant_maqra_open');
            $table->unsignedInteger('participant_maqra_lot_max')->nullable()->after('participant_maqra_lot_min');
        });
    }

    public function down(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            $table->dropColumn(['participant_maqra_lot_min', 'participant_maqra_lot_max']);
        });
    }
};
