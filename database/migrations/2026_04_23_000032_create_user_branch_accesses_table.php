<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_branch_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('branch');
            $table->timestamps();

            $table->unique(['user_id', 'branch']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branch_accesses');
    }
};
