<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'mandate_verified_by')) {
                $table->dropConstrainedForeignId('mandate_verified_by');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'mandate_document_path') ? 'mandate_document_path' : null,
                Schema::hasColumn('users', 'mandate_uploaded_at') ? 'mandate_uploaded_at' : null,
                Schema::hasColumn('users', 'mandate_status') ? 'mandate_status' : null,
                Schema::hasColumn('users', 'mandate_verification_notes') ? 'mandate_verification_notes' : null,
                Schema::hasColumn('users', 'mandate_verified_at') ? 'mandate_verified_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'mandate_document_path')) {
                $table->string('mandate_document_path')->nullable()->after('district_id');
            }

            if (! Schema::hasColumn('users', 'mandate_uploaded_at')) {
                $table->timestamp('mandate_uploaded_at')->nullable()->after('mandate_document_path');
            }

            if (! Schema::hasColumn('users', 'mandate_status')) {
                $table->string('mandate_status')->nullable()->after('mandate_uploaded_at');
            }

            if (! Schema::hasColumn('users', 'mandate_verification_notes')) {
                $table->text('mandate_verification_notes')->nullable()->after('mandate_status');
            }

            if (! Schema::hasColumn('users', 'mandate_verified_by')) {
                $table->foreignId('mandate_verified_by')->nullable()->after('mandate_verification_notes')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'mandate_verified_at')) {
                $table->timestamp('mandate_verified_at')->nullable()->after('mandate_verified_by');
            }
        });
    }
};
