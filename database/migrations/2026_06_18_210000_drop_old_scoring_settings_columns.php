<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE scoring_settings
            DROP FOREIGN KEY scoring_settings_edit_requested_by_foreign,
            DROP FOREIGN KEY scoring_settings_edit_opened_by_foreign
        ');

        DB::statement('ALTER TABLE scoring_settings
            DROP COLUMN IF EXISTS judge_count,
            DROP COLUMN IF EXISTS judge_names,
            DROP COLUMN IF EXISTS scoring_points,
            DROP COLUMN IF EXISTS judging_rounds,
            DROP COLUMN IF EXISTS scoring_priorities,
            DROP COLUMN IF EXISTS round_settings,
            DROP COLUMN IF EXISTS edit_state,
            DROP COLUMN IF EXISTS edit_requested_at,
            DROP COLUMN IF EXISTS edit_requested_by,
            DROP COLUMN IF EXISTS edit_opened_at,
            DROP COLUMN IF EXISTS edit_opened_by
        ');
    }

    public function down(): void
    {
        // Not provided
    }
};
