<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->dropUnique(['lot_number']);
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->unique('lot_number');
        });
    }
};
