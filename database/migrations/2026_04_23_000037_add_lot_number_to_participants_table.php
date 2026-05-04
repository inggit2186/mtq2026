<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->string('lot_number', 20)->nullable()->unique()->after('verification_status');
            $table->timestamp('lot_assigned_at')->nullable()->after('lot_number');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->dropUnique(['lot_number']);
            $table->dropColumn(['lot_number', 'lot_assigned_at']);
        });
    }
};
