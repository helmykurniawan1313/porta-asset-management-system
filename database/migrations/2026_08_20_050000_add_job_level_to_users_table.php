<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the old overloaded `job_title` column into two concepts:
 * `job_title` becomes a free-text role name (e.g. "IT Support", "UI
 * Designer"), while `job_level` holds seniority (Intern, Probation, Junior
 * Associate, Associate, Senior Associate). Existing rows whose job_title
 * held a seniority value are moved to job_level; "Head of Department" stays
 * in job_title since it's a title, not a level.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('job_level')->nullable()->after('job_title');
        });

        $levels = ['Intern', 'Probation', 'Junior Associate', 'Associate', 'Senior Associate'];

        DB::table('users')->whereIn('job_title', $levels)->get(['id', 'job_title'])->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'job_level' => $user->job_title,
                'job_title' => null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('job_level');
        });
    }
};
