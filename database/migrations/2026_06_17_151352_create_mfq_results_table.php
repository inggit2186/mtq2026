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
        Schema::create('mfq_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mfq_session_id')->constrained('mfq_sessions')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            $table->string('round')->comment('Round penilaian: Penyisihan, Semi Final, Final');
            $table->integer('rank')->comment('Peringkat kecamatan dalam sesi');
            $table->decimal('total_score', 10, 2)->comment('Total nilai dari semua hakim');
            $table->json('scores_detail')->nullable()->comment('Detail nilai per hakim');
            $table->timestamps();

            $table->unique(['mfq_session_id', 'participant_id']);
            $table->index(['mfq_session_id', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mfq_results');
    }
};
