<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('judge_name');
            $table->decimal('score', 5, 2);
            $table->string('remarks')->nullable();
            $table->timestamp('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_entries');
    }
};
