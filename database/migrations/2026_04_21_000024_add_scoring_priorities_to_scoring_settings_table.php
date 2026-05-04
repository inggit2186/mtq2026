<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scoring_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('scoring_settings', 'scoring_priorities')) {
                $table->json('scoring_priorities')->nullable()->after('scoring_points');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scoring_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('scoring_settings', 'scoring_priorities')) {
                $table->dropColumn('scoring_priorities');
            }
        });
    }
};
