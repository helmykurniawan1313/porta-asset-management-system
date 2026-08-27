<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * job_title is retired in favor of just using the employee's department
 * everywhere it was shown. The "Head of Department" value it held was
 * display-only — Department::head_of_department_id is the real source of
 * truth for who leads a department, so no data is lost by dropping this.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('job_title');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title')->nullable()->after('department_id');
        });
    }
};
