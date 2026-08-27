<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * job_title was originally created as a MySQL ENUM (locked to seniority
 * values plus "Head of Department"). Now that seniority lives in job_level,
 * job_title needs to hold arbitrary free text (e.g. "IT Support", "UI
 * Designer"), so the column itself must change type, not just app-level
 * validation.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY job_title VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY job_title ENUM('Intern','Probation','Junior Associate','Associate','Senior Associate','Head of Department') NULL");
    }
};
