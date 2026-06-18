<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            // competition_role: 'khatib' or 'muadzin' for Khutbah/Adzan related participants
            // This is used to track the role when splitting from combined category
            $table->string('competition_role', 20)->nullable()->after('participant_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->dropColumn('competition_role');
        });
    }
};
