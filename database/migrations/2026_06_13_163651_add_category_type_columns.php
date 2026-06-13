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
        Schema::table('competition_categories', function (Blueprint $table) {
            // Maqra system type: 'tilawah', 'tahfizh', 'tafsir', 'fahmil', 'syarhil', 'khatib', 'muadzin'
            $table->string('maqra_system_type')->nullable()->after('notes');
            // Lot grouping type: 'single' (1 orang), 'pair' (2 orang/kecamatan), 'triple' (3 orang/kecamatan)
            $table->string('lot_group_type')->nullable()->after('maqra_system_type');
            // District quota: apakah category menggunakan sistem KK (district quota)
            $table->boolean('uses_district_quota')->default(false)->after('lot_group_type');
        });

        // Auto-fill data based on existing branch names
        $this->autoFillCategoryTypes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_categories', function (Blueprint $table) {
            $table->dropColumn(['maqra_system_type', 'lot_group_type', 'uses_district_quota']);
        });
    }

    /**
     * Auto-fill category types based on existing branch names.
     */
    private function autoFillCategoryTypes(): void
    {
        $categories = DB::table('competition_categories')->get();

        foreach ($categories as $category) {
            $branch = mb_strtolower((string) ($category->branch ?? ''));
            $name = mb_strtolower((string) ($category->name ?? ''));
            $notes = mb_strtolower((string) ($category->notes ?? ''));

            $updates = [];

            // Determine maqra_system_type
            if (str_contains($branch, 'seni baca al qur')) {
                $updates['maqra_system_type'] = 'tilawah';
            } elseif (str_contains($branch, 'hafalan al qur')) {
                $updates['maqra_system_type'] = 'tahfizh';
            } elseif (str_contains($branch, 'tafsir al qur')) {
                $updates['maqra_system_type'] = 'tafsir';
            } elseif (str_contains($branch, 'fahmil qur')) {
                $updates['maqra_system_type'] = 'fahmil';
            } elseif (str_contains($branch, 'syarhil qur')) {
                $updates['maqra_system_type'] = 'syarhil';
            } elseif (str_contains($branch, 'khatib')) {
                $updates['maqra_system_type'] = 'khatib';
            } elseif (str_contains($branch, 'muadzin')) {
                $updates['maqra_system_type'] = 'muadzin';
            }

            // Determine lot_group_type
            if (str_contains($branch, 'fahmil') || str_contains($branch, 'syarhil')) {
                $updates['lot_group_type'] = 'triple';
            } elseif (str_contains($branch, 'khutbah') || str_contains($branch, 'adzan')) {
                $updates['lot_group_type'] = 'pair';
            } else {
                $updates['lot_group_type'] = 'single';
            }

            // Determine uses_district_quota
            $updates['uses_district_quota'] = str_contains($notes, 'kk') ? true : false;

            if (! empty($updates)) {
                DB::table('competition_categories')
                    ->where('id', $category->id)
                    ->update($updates);
            }
        }
    }
};
