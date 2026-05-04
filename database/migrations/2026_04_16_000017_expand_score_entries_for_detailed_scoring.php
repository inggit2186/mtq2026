<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('score_entries', function (Blueprint $table): void {
            $table->string('judging_round')->nullable()->after('judge_name');
            $table->json('score_breakdown')->nullable()->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('score_entries', function (Blueprint $table): void {
            $table->dropColumn([
                'judging_round',
                'score_breakdown',
            ]);
        });
    }
};
