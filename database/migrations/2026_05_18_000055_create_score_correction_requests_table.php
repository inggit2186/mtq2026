<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_correction_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->cascadeOnDelete();
            $table->string('judging_round');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->json('requested_scores')->nullable();
            $table->json('requested_remarks')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();

            $table->index(['participant_id', 'judging_round', 'status'], 'score_correction_requests_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_correction_requests');
    }
};
