<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('id')
                ->constrained('departments')->nullOnDelete();
            $table->enum('job_title', [
                'Intern',
                'Probation',
                'Junior Associate',
                'Associate',
                'Senior Associate',
                'Head of Department',
            ])->nullable()->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('job_title');
        });
    }
};
