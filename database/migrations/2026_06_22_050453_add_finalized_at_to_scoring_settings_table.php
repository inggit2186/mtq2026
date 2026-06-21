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
        Schema::table('scoring_settings', function (Blueprint $table) {
            $table->timestamp('penyisihan_finalized_at')->nullable()->after('penyisihan_edit_opened_at');
            $table->timestamp('final_finalized_at')->nullable()->after('final_edit_opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scoring_settings', function (Blueprint $table) {
            $table->dropColumn(['penyisihan_finalized_at', 'final_finalized_at']);
        });
    }
};
