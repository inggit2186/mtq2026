<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maqra_packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->cascadeOnDelete();
            $table->string('round_label', 30)->default('Penyisihan');
            $table->string('maqra_code', 30);
            $table->string('title');
            $table->longText('content');
            $table->string('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['competition_category_id', 'round_label', 'is_active'], 'maqra_packages_category_round_active_index');
            $table->unique(['competition_category_id', 'round_label', 'maqra_code'], 'maqra_packages_unique_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maqra_packages');
    }
};
