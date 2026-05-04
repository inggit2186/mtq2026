<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('mandate_status')->nullable()->after('mandate_uploaded_at');
            $table->text('mandate_verification_notes')->nullable()->after('mandate_status');
            $table->foreignId('mandate_verified_by')->nullable()->after('mandate_verification_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('mandate_verified_at')->nullable()->after('mandate_verified_by');
        });

        DB::table('users')
            ->whereNotNull('mandate_document_path')
            ->whereNull('mandate_status')
            ->update(['mandate_status' => 'submitted']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('mandate_verified_by');
            $table->dropColumn(['mandate_status', 'mandate_verification_notes', 'mandate_verified_at']);
        });
    }
};
