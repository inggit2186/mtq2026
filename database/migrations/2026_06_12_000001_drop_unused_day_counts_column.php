<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appearance_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('appearance_schedules', 'day_counts')) {
                $table->dropColumn('day_counts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appearance_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('appearance_schedules', 'day_counts')) {
                $table->json('day_counts')->nullable();
            }
        });
    }
};