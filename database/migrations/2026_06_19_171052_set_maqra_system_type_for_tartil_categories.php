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
        // Set maqra_system_type to 'tartil' for all Tartil Al Qur'an categories
        DB::table('competition_categories')
            ->whereRaw('LOWER(branch) LIKE ?', ['%tartil%'])
            ->whereNull('maqra_system_type')
            ->update(['maqra_system_type' => 'tartil']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert maqra_system_type back to null for Tartil categories
        DB::table('competition_categories')
            ->whereRaw('LOWER(branch) LIKE ?', ['%tartil%'])
            ->where('maqra_system_type', 'tartil')
            ->update(['maqra_system_type' => null]);
    }
};
