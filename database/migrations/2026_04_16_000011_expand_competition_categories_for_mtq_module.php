<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_categories', function (Blueprint $table): void {
            $table->string('branch')->nullable()->after('id');
            $table->unsignedSmallInteger('quota')->default(2)->after('slug');
            $table->string('age_requirement')->nullable()->after('quota');
            $table->string('notes')->nullable()->after('age_requirement');
            $table->unsignedInteger('sort_order')->default(0)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('competition_categories', function (Blueprint $table): void {
            $table->dropColumn([
                'branch',
                'quota',
                'age_requirement',
                'notes',
                'sort_order',
            ]);
        });
    }
};
