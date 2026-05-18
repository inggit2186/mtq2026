<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            $table->boolean('participant_delete_open')->default(true)->after('participant_edit_open');
        });
    }

    public function down(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            $table->dropColumn('participant_delete_open');
        });
    }
};
