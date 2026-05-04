<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_categories', function (Blueprint $table): void {
            $table->string('lot_code', 10)->nullable()->after('color');
            $table->unsignedSmallInteger('lot_number_min')->nullable()->after('lot_code');
            $table->unsignedSmallInteger('lot_number_max')->nullable()->after('lot_number_min');
        });
    }

    public function down(): void
    {
        Schema::table('competition_categories', function (Blueprint $table): void {
            $table->dropColumn([
                'lot_code',
                'lot_number_min',
                'lot_number_max',
            ]);
        });
    }
};
