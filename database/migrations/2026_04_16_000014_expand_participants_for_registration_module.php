<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->foreignId('district_id')->nullable()->after('competition_category_id')->constrained('districts')->nullOnDelete();
            $table->string('gender', 20)->nullable()->after('name');
            $table->string('nik', 32)->nullable()->after('gender');
            $table->string('place_of_birth')->nullable()->after('nik');
            $table->date('date_of_birth')->nullable()->after('place_of_birth');
            $table->string('phone')->nullable()->after('date_of_birth');
            $table->string('document_kk')->nullable()->after('avatar');
            $table->string('document_ktp')->nullable()->after('document_kk');
            $table->string('document_birth_certificate')->nullable()->after('document_ktp');
            $table->string('document_photo')->nullable()->after('document_birth_certificate');
            $table->string('verification_status')->default('draft')->after('status');
            $table->text('verification_notes')->nullable()->after('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('district_id');
            $table->dropColumn([
                'gender',
                'nik',
                'place_of_birth',
                'date_of_birth',
                'phone',
                'document_kk',
                'document_ktp',
                'document_birth_certificate',
                'document_photo',
                'verification_status',
                'verification_notes',
            ]);
        });
    }
};
