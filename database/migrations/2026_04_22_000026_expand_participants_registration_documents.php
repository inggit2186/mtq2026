<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->date('ktp_date')->nullable()->after('nik');
            $table->string('kk_number')->nullable()->after('date_of_birth');
            $table->date('kk_date')->nullable()->after('kk_number');
            $table->string('last_education')->nullable()->after('institution');
            $table->string('bank_name')->nullable()->after('last_education');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_name')->nullable()->after('bank_account_number');
            $table->string('document_last_diploma')->nullable()->after('document_photo');
            $table->string('document_bank_book')->nullable()->after('document_last_diploma');
            $table->json('document_certificates')->nullable()->after('document_bank_book');
            $table->json('document_other_files')->nullable()->after('document_certificates');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->dropColumn([
                'ktp_date',
                'kk_number',
                'kk_date',
                'last_education',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'document_last_diploma',
                'document_bank_book',
                'document_certificates',
                'document_other_files',
            ]);
        });
    }
};
