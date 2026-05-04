<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_district_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'district_id'], 'user_district_access_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_district_accesses');
    }
};
