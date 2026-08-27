<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY status ENUM('not_started', 'active', 'completed', 'terminated') NOT NULL DEFAULT 'not_started'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY status ENUM('active', 'completed', 'terminated') NOT NULL DEFAULT 'active'");
    }
};
