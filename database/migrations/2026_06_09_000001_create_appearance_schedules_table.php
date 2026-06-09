<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appearance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_category_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number_of_days');
            $table->json('day_schedules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('competition_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appearance_schedules');
    }
};
