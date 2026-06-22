<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            $table->boolean('official_can_view_score_detail')->default(false)->after('mandate_upload_open');
        });
    }

    public function down(): void
    {
        Schema::table('official_access_settings', function (Blueprint $table): void {
            $table->dropColumn('official_can_view_score_detail');
        });
    }
};
