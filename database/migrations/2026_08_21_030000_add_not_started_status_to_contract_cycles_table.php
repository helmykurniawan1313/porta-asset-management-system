<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE contract_cycles MODIFY status ENUM('not_started', 'on_track', 'behind_schedule', 'completed') NOT NULL DEFAULT 'not_started'");

        // Existing cycles created before this change defaulted to on_track
        // even though no actual_start was ever recorded — those are really
        // "not started" in truth, since nothing began yet.
        DB::table('contract_cycles')
            ->where('status', 'on_track')
            ->whereNull('actual_start')
            ->update(['status' => 'not_started']);
    }

    public function down(): void
    {
        DB::statement("UPDATE contract_cycles SET status = 'on_track' WHERE status = 'not_started'");
        DB::statement("ALTER TABLE contract_cycles MODIFY status ENUM('on_track', 'behind_schedule', 'completed') NOT NULL DEFAULT 'on_track'");
    }
};
