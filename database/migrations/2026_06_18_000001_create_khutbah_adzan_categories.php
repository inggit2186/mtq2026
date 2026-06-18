<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create "Khutbah Jumat" category
        $khutbahId = DB::table('competition_categories')->insertGetId([
            'branch' => 'Khutbah Jumat',
            'name' => 'Khatib',
            'slug' => 'khutbah-jumat-khatib',
            'quota' => 1,
            'age_requirement' => 'Minimal 16 tahun, maksimal 18 tahun 11 bulan 29 hari',
            'notes' => 'KK Kecamatan - 1 putra',
            'maqra_system_type' => null, // Tidak pakai maqra
            'lot_group_type' => 'single',
            'uses_district_quota' => true,
            'sort_order' => 24,
            'round' => 'Penyisihan',
            'description' => 'Kategori resmi MTQ - Khutbah Jumat',
            'color' => '#14b8a6',
            'lot_code' => 'KHJ',
            'lot_number_min' => 1,
            'lot_number_max' => 28,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create "Adzan" category
        $adzanId = DB::table('competition_categories')->insertGetId([
            'branch' => 'Adzan',
            'name' => 'Muadzin',
            'slug' => 'adzan-muadzin',
            'quota' => 1,
            'age_requirement' => 'Minimal 16 tahun, maksimal 18 tahun 11 bulan 29 hari',
            'notes' => 'KK Kecamatan - 1 putra',
            'maqra_system_type' => null, // Tidak pakai maqra
            'lot_group_type' => 'single',
            'uses_district_quota' => true,
            'sort_order' => 25,
            'round' => 'Penyisihan',
            'description' => 'Kategori resmi MTQ - Adzan',
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
        DB::table('competition_categories')
            ->where('slug', 'khutbah-jumat-khatib')
            ->delete();

        DB::table('competition_categories')
            ->where('slug', 'adzan-muadzin')
            ->delete();
    }
};
