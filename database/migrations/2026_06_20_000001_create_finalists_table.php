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
        Schema::create('finalists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            $table->foreignId('competition_category_id')->constrained('competition_categories')->onDelete('cascade');
            $table->string('gender'); // 'male' (putra) or 'female' (putri)
            $table->tinyInteger('finalist_rank'); // 1, 2, or 3 (top 3 per gender per category)
            $table->decimal('score', 8, 2)->default(0); // Score at time of generation
            $table->string('round')->default('Penyisihan'); // Which round qualified them (e.g., 'Penyisihan', 'Final')
            $table->enum('status', ['pending', 'active', 'completed', 'scratched'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index for efficient queries
            $table->index(['competition_category_id', 'gender', 'finalist_rank']);
            $table->index(['competition_category_id', 'status']);
            $table->unique(['participant_id', 'competition_category_id'], 'unique_participant_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finalists');
    }
};
