<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maqra_schedules', function (Blueprint $table) {
            $table->enum('draw_access_by', ['panitia_only', 'official_only', 'both'])
                  ->default('official_only')
                  ->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('maqra_schedules', function (Blueprint $table) {
            $table->dropColumn('draw_access_by');
        });
    }
};
