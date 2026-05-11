<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_participant_id')->nullable()->index();
            $table->unsignedBigInteger('competition_category_id')->index();
            $table->unsignedBigInteger('district_id')->nullable()->index();
            $table->string('registration_number')->unique();
            $table->string('participant_role', 20)->nullable();
            $table->string('name');
            $table->string('gender', 20)->nullable();
            $table->string('nik', 32)->nullable()->index();
            $table->date('ktp_date')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('kk_number')->nullable();
            $table->date('kk_date')->nullable();
            $table->string('phone')->nullable();
            $table->string('institution')->nullable();
            $table->string('last_education')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->text('current_address')->nullable();
            $table->text('ktp_address')->nullable();
            $table->string('ktp_district')->nullable();
            $table->string('ktp_regency')->nullable();
            $table->string('region')->nullable();
            $table->string('avatar')->nullable();
            $table->string('document_kk')->nullable();
            $table->string('document_ktp')->nullable();
            $table->string('document_birth_certificate')->nullable();
            $table->string('document_photo')->nullable();
            $table->string('document_last_diploma')->nullable();
            $table->string('document_bank_book')->nullable();
            $table->json('document_certificates')->nullable();
            $table->json('document_other_files')->nullable();
            $table->string('status')->default('active');
            $table->string('verification_status')->default('draft');
            $table->integer('lot_number')->nullable();
            $table->timestamp('lot_assigned_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->json('document_revision_notes')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_participants');
    }
};
