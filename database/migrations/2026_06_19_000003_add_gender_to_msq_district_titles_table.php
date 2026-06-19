<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('msq_district_titles', function (Blueprint $table): void {
            $table->enum('gender', ['putra', 'putri'])->default('putra')->after('district_id');
        });

        // Add composite unique index with gender
        Schema::table('msq_district_titles', function (Blueprint $table): void {
            $table->dropUnique(['district_id', 'title']);
            $table->unique(['district_id', 'gender', 'title'], 'msq_district_titles_district_gender_title_unique');
            $table->index(['district_id', 'gender', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('msq_district_titles', function (Blueprint $table): void {
            $table->dropUnique(['district_id', 'gender', 'title']);
            $table->dropColumn('gender');
            $table->unique(['district_id', 'title']);
            $table->dropIndex(['district_id', 'gender', 'is_active']);
        });
    }
};
