<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appearance_schedules', function (Blueprint $table) {
            $table->json('day_schedules')->nullable()->after('number_of_days');
            $table->dropColumn(['name', 'schedule_date', 'schedule_time']);
        });
    }

    public function down(): void
    {
        Schema::table('appearance_schedules', function (Blueprint $table) {
            $table->string('name')->nullable()->after('competition_category_id');
            $table->date('schedule_date')->nullable()->after('name');
            $table->time('schedule_time')->nullable()->after('schedule_date');
            $table->dropColumn('day_schedules');
        });
    }
};
