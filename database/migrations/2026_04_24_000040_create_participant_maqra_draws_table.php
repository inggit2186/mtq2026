<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_maqra_draws', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->foreignId('maqra_package_id')->constrained('maqra_packages')->cascadeOnDelete();
            $table->string('round_label', 30)->default('Penyisihan');
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();

            $table->unique(['participant_id', 'round_label'], 'participant_maqra_draws_unique_round');
            $table->index(['maqra_package_id', 'round_label'], 'participant_maqra_draws_package_round_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_maqra_draws');
    }
};
