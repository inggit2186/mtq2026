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
        Schema::create('ranking_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Display name for this ranking configuration');
            $table->foreignId('competition_category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('gender', ['putra', 'putri', 'all'])->default('all')->comment('Gender filter: putra, putri, or all');
            $table->integer('appearance_day')->nullable()->comment('Specific day index (0-based), null for overall');
            $table->string('judging_round')->default('Penyisihan')->comment('Judging round: Penyisihan or Final');
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->boolean('is_active')->default(true)->comment('Whether this ranking is active');
            $table->timestamps();

            $table->index(['competition_category_id', 'is_active', 'sort_order'], 'idx_ranking_category');
            $table->index(['is_active', 'sort_order'], 'idx_ranking_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranking_settings');
    }
};
