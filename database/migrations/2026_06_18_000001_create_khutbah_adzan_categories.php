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
     * Dengan Pendekatan B:
     * - Branch tetap "Khutbah Jumat dan Adzan" untuk kedua kategori baru
     * - Soft delete category lama (pair: "Khatib dan Muadzin")
     * - Buat 2 category baru (solo): "Khatib" dan "Adzan"
     */
    public function up(): void
    {
        // 1. Soft delete category lama (pair: Khatib dan Muadzin)
        // Ini akan memindahkan category lama ke "archived" - peserta yang sudah ada tetap bisa diakses
        DB::table('competition_categories')
            ->where('slug', 'khutbah-jumat-dan-adzan-khatib-dan-muadzin')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        // 2. Buat category baru "Khatib" (solo) - Branch SAMA, Name BERBEDA
        DB::table('competition_categories')->insert([
            'branch' => 'Khutbah Jumat dan Adzan',
            'name' => 'Khatib',
            'slug' => 'khutbah-jumat-dan-adzan-khatib',
            'quota' => 1,
            'age_requirement' => 'Minimal 16 tahun, maksimal 18 tahun 11 bulan 29 hari',
            'notes' => 'KK Kecamatan - 1 putra',
            'maqra_system_type' => null, // Tidak pakai maqra
            'lot_group_type' => 'single',
            'uses_district_quota' => true,
            'sort_order' => 24,
            'round' => 'Penyisihan',
            'description' => 'Kategori resmi MTQ - Khutbah Jumat dan Adzan Golongan Khatib',
            'color' => '#14b8a6',
            'lot_code' => 'KHJ',
            'lot_number_min' => 1,
            'lot_number_max' => 28,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Buat category baru "Adzan" (solo) - Branch SAMA, Name BERBEDA
        DB::table('competition_categories')->insert([
            'branch' => 'Khutbah Jumat dan Adzan',
            'name' => 'Adzan',
            'slug' => 'khutbah-jumat-dan-adzan-adzan',
            'quota' => 1,
            'age_requirement' => 'Minimal 16 tahun, maksimal 18 tahun 11 bulan 29 hari',
            'notes' => 'KK Kecamatan - 1 putra',
            'maqra_system_type' => null, // Tidak pakai maqra
            'lot_group_type' => 'single',
            'uses_district_quota' => true,
            'sort_order' => 25,
            'round' => 'Penyisihan',
            'description' => 'Kategori resmi MTQ - Khutbah Jumat dan Adzan Golongan Adzan',
            'color' => '#f59e0b',
            'lot_code' => 'ADZ',
            'lot_number_min' => 1,
            'lot_number_max' => 28,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Hapus category baru
        DB::table('competition_categories')
            ->whereIn('slug', [
                'khutbah-jumat-dan-adzan-khatib',
                'khutbah-jumat-dan-adzan-adzan',
            ])
            ->delete();

        // 2. Restore category lama
        DB::table('competition_categories')
            ->where('slug', 'khutbah-jumat-dan-adzan-khatib-dan-muadzin')
            ->update([
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
    }
};
