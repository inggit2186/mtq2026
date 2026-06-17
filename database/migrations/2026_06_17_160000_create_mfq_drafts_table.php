<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mfq_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mfq_session_id')->constrained('mfq_sessions')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->string('judge_name', 80);
            $table->json('questions_data');
            $table->json('totals');
            $table->timestamps();

            // Unique constraint for idempotency - one draft per session, participant, judge combination
            $table->unique(['mfq_session_id', 'participant_id', 'judge_name'], 'mfq_drafts_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfq_drafts');
    }
};
