<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropColumnIfExists('scoring_settings', 'judge_count');
        $this->dropColumnIfExists('scoring_settings', 'judge_names');
        $this->dropColumnIfExists('scoring_settings', 'scoring_points');
        $this->dropColumnIfExists('scoring_settings', 'judging_rounds');
        $this->dropColumnIfExists('scoring_settings', 'scoring_priorities');
        $this->dropColumnIfExists('scoring_settings', 'round_settings');
        $this->dropColumnIfExists('scoring_settings', 'edit_state');
        $this->dropColumnIfExists('scoring_settings', 'edit_requested_at');
        $this->dropColumnIfExists('scoring_settings', 'edit_requested_by');
        $this->dropColumnIfExists('scoring_settings', 'edit_opened_at');
        $this->dropColumnIfExists('scoring_settings', 'edit_opened_by');
    }

    public function down(): void
    {
        //
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        if (Schema::hasColumn($table, $column)) {
            DB::statement("ALTER TABLE {$table} DROP COLUMN {$column}");
        }
    }
};
