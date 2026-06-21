<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ranking_settings', function (Blueprint $table) {
            $table->foreignId('finalist_category_id')->nullable()->after('competition_category_id')->constrained('competition_categories')->nullOnDelete();
            $table->string('finalist_display_name')->nullable()->after('finalist_category_id')->comment('Custom display name for finalist announcement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ranking_settings', function (Blueprint $table) {
            $table->dropForeign(['finalist_category_id']);
            $table->dropColumn(['finalist_category_id', 'finalist_display_name']);
        });
    }
};
