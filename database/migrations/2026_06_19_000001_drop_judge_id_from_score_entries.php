<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('score_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('score_entries', 'judge_id')) {
                $table->dropIndex(['judge_id']);
                $table->dropColumn('judge_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('score_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('score_entries', 'judge_id')) {
                $table->unsignedBigInteger('judge_id')->nullable()->after('judge_name');
                $table->index('judge_id');
            }
        });
    }
};
