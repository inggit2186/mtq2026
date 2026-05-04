<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('mandate_document_path')->nullable()->after('district_id');
            $table->timestamp('mandate_uploaded_at')->nullable()->after('mandate_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['mandate_document_path', 'mandate_uploaded_at']);
        });
    }
};
