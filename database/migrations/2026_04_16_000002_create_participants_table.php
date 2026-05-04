<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_category_id')->constrained()->cascadeOnDelete();
            $table->string('registration_number')->unique();
            $table->string('name');
            $table->string('institution');
            $table->string('region')->nullable();
            $table->string('avatar')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
