<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hakim', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('asal')->nullable();
            $table->timestamps();
        });

        Schema::create('hakim_golongan', function (Blueprint $table) {
            $table->foreignId('hakim_id')->constrained('hakim')->onDelete('cascade');
            $table->foreignId('golongan_id')->constrained('competition_categories')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['hakim_id', 'golongan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hakim_golongan');
        Schema::dropIfExists('hakim');
    }
};
