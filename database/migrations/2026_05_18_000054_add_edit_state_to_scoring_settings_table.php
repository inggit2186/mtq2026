<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scoring_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('scoring_settings', 'edit_state')) {
                $table->string('edit_state', 20)->default('locked')->after('configured_by');
            }

            if (! Schema::hasColumn('scoring_settings', 'edit_requested_at')) {
                $table->timestamp('edit_requested_at')->nullable()->after('edit_state');
            }

            if (! Schema::hasColumn('scoring_settings', 'edit_requested_by')) {
                $table->foreignId('edit_requested_by')->nullable()->after('edit_requested_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('scoring_settings', 'edit_opened_at')) {
                $table->timestamp('edit_opened_at')->nullable()->after('edit_requested_by');
            }

            if (! Schema::hasColumn('scoring_settings', 'edit_opened_by')) {
                $table->foreignId('edit_opened_by')->nullable()->after('edit_opened_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('scoring_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('scoring_settings', 'edit_opened_by')) {
                $table->dropConstrainedForeignId('edit_opened_by');
            }

            if (Schema::hasColumn('scoring_settings', 'edit_opened_at')) {
                $table->dropColumn('edit_opened_at');
            }

            if (Schema::hasColumn('scoring_settings', 'edit_requested_by')) {
                $table->dropConstrainedForeignId('edit_requested_by');
            }

            if (Schema::hasColumn('scoring_settings', 'edit_requested_at')) {
                $table->dropColumn('edit_requested_at');
            }

            if (Schema::hasColumn('scoring_settings', 'edit_state')) {
                $table->dropColumn('edit_state');
            }
        });
    }
};
