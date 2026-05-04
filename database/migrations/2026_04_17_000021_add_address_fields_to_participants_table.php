<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->text('current_address')->nullable()->after('institution');
            $table->text('ktp_address')->nullable()->after('current_address');
            $table->string('ktp_district')->nullable()->after('ktp_address');
            $table->string('ktp_regency')->nullable()->after('ktp_district');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->dropColumn(['current_address', 'ktp_address', 'ktp_district', 'ktp_regency']);
        });
    }
};
