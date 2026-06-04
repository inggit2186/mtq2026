<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('score_entries', function (Blueprint $table): void {
            // Make judge_name nullable since new aggregated format doesn't use it
            $table->string('judge_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('score_entries', function (Blueprint $table): void {
            $table->string('judge_name')->nullable(false)->change();
        });
    }
};