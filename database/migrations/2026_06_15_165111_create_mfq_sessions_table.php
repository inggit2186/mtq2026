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
        Schema::create('mfq_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('competition_category_id')->constrained()->cascadeOnDelete();
            $table->enum('round', ['Penyisihan', 'Final'])->default('Penyisihan');
            $table->json('judges')->comment('Array of judge names');
            $table->json('district_ids')->comment('Array of selected district IDs (2-5)');
            $table->string('status')->default('active')->comment('active, completed, cancelled');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['competition_category_id', 'status']);
            $table->index(['status', 'round']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mfq_sessions');
    }
};
