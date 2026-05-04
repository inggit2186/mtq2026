<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('districts', function (Blueprint $table): void {
            $table->unsignedBigInteger('silatar_id')->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('districts', function (Blueprint $table): void {
            $table->dropUnique(['silatar_id']);
            $table->dropColumn('silatar_id');
        });
    }
};
