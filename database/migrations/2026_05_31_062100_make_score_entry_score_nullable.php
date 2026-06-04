<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('score_entries', function (Blueprint $table): void {
            // Make score nullable since new aggregated format uses average_score instead
            $table->decimal('score', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('score_entries', function (Blueprint $table): void {
            $table->decimal('score', 5, 2)->nullable(false)->change();
        });
    }
};