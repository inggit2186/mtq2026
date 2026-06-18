<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix categories to Approach B:
     * - Branch tetap "Khutbah Jumat dan Adzan" untuk kedua kategori baru
     * - Update name dan slug untuk konsistensi
     */
    public function up(): void
    {
        // 1. Update "Khutbah Jumat" category to Approach B format
        DB::table('competition_categories')
            ->where('slug', 'khutbah-jumat-khatib')
            ->update([
                'branch' => 'Khutbah Jumat dan Adzan',
                'name' => 'Khatib',
                'slug' => 'khutbah-jumat-dan-adzan-khatib',
                'description' => 'Kategori resmi MTQ - Khutbah Jumat dan Adzan Golongan Khatib',
                'updated_at' => now(),
            ]);

        // 2. Update "Adzan" category to Approach B format
        DB::table('competition_categories')
            ->where('slug', 'adzan-muadzin')
            ->update([
                'branch' => 'Khutbah Jumat dan Adzan',
                'name' => 'Adzan',
                'slug' => 'khutbah-jumat-dan-adzan-adzan',
                'description' => 'Kategori resmi MTQ - Khutbah Jumat dan Adzan Golongan Adzan',
                'updated_at' => now(),
            ]);

        // 3. Soft delete old combined category (pair)
        DB::table('competition_categories')
            ->where('slug', 'khutbah-jumat-dan-adzan-khatib-dan-muadzin')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Restore old combined category
        DB::table('competition_categories')
            ->where('slug', 'khutbah-jumat-dan-adzan-khatib-dan-muadzin')
            ->update([
                'deleted_at' => null,
                'updated_at' => now(),
            ]);

        // 2. Revert Khatib category to Approach A format
        DB::table('competition_categories')
            ->where('slug', 'khutbah-jumat-dan-adzan-khatib')
            ->update([
                'branch' => 'Khutbah Jumat',
                'name' => 'Khatib',
                'slug' => 'khutbah-jumat-khatib',
                'description' => 'Kategori resmi MTQ - Khutbah Jumat',
                'updated_at' => now(),
            ]);

        // 3. Revert Adzan category to Approach A format
        DB::table('competition_categories')
            ->where('slug', 'khutbah-jumat-dan-adzan-adzan')
            ->update([
                'branch' => 'Adzan',
                'name' => 'Muadzin',
                'slug' => 'adzan-muadzin',
                'description' => 'Kategori resmi MTQ - Adzan',
                'updated_at' => now(),
            ]);
    }
};
