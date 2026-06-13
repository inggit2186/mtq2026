<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appearance_schedules', function (Blueprint $table) {
            // Only modify day_schedules - keep columns as-is
            // If you need to drop schedule_date/schedule_time, do it separately
            $table->json('day_schedules')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No rollback needed for json modification
    }
};
