<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_category_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('judge_count')->default(1);
            $table->json('judge_names');
            $table->json('judging_rounds');
            $table->json('scoring_points');
            $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('competition_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_settings');
    }
};
