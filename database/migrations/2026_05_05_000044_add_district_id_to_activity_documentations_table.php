<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_documentations', function (Blueprint $table): void {
            $table->foreignId('district_id')->nullable()->after('uploaded_by')->constrained('districts')->nullOnDelete();
            $table->index(['district_id', 'is_active', 'sort_order', 'created_at'], 'activity_documentations_district_active_sort_created_index');
        });

        DB::table('activity_documentations')
            ->select(['id', 'uploaded_by'])
            ->whereNull('district_id')
            ->whereNotNull('uploaded_by')
            ->orderBy('id')
            ->chunkById(200, function ($items): void {
                $userDistrictMap = DB::table('users')
                    ->whereIn('id', $items->pluck('uploaded_by')->filter()->unique()->all())
                    ->whereNotNull('district_id')
                    ->pluck('district_id', 'id');

                foreach ($items as $item) {
                    $districtId = $userDistrictMap->get($item->uploaded_by);

                    if (! $districtId) {
                        continue;
                    }

                    DB::table('activity_documentations')
                        ->where('id', $item->id)
                        ->update(['district_id' => $districtId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('activity_documentations', function (Blueprint $table): void {
            $table->dropIndex('activity_documentations_district_active_sort_created_index');
            $table->dropConstrainedForeignId('district_id');
        });
    }
};
