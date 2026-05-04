<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->json('document_revision_notes')->nullable()->after('verification_notes');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->dropColumn('document_revision_notes');
        });
    }
};
