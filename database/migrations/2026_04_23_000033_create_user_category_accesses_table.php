<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_category_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'competition_category_id'], 'user_category_access_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_category_accesses');
    }
};
