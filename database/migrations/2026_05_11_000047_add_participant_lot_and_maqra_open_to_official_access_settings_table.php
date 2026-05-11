<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            $table->boolean('participant_lot_open')->default(true)->after('participant_verification_open');
            $table->boolean('participant_maqra_open')->default(true)->after('participant_lot_open');
        });
    }

    public function down(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            $table->dropColumn(['participant_maqra_open', 'participant_lot_open']);
        });
    }
};
