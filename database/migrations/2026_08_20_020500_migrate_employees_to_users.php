<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * One-time data migration for the Employee -> User merge. it-inventory's
 * separate `employees` table is retired; every employee becomes a `users`
 * row instead. Existing employees are matched to a user by email; unmatched
 * employees get a fresh User with a random unusable password (they can be
 * reconciled later via a real password reset). Every employee_id foreign key
 * (devices, activity_logs, subscription_allocations.assignable_*) is remapped
 * to the corresponding user_id, then the employees table and its FK columns
 * are dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        $employeeIdToUserId = [];

        foreach (DB::table('employees')->get() as $employee) {
            $userId = null;

            if ($employee->email) {
                $existing = DB::table('users')->where('email', $employee->email)->first();
                if ($existing) {
                    $userId = $existing->id;
                    DB::table('users')->where('id', $userId)->update([
                        'department_id' => DB::table('users')->where('id', $userId)->value('department_id') ?? $employee->department_id,
                    ]);
                }
            }

            if (! $userId) {
                $email = $employee->email ?: 'employee-'.$employee->id.'@placeholder.local';

                $userId = DB::table('users')->insertGetId([
                    'name' => $employee->name ?: 'Unnamed Employee',
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'department_id' => $employee->department_id,
                    'created_at' => $employee->created_at,
                    'updated_at' => now(),
                ]);
            }

            $employeeIdToUserId[$employee->id] = $userId;
        }

        if (Schema::hasColumn('devices', 'employee_id')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('employee_id')
                    ->constrained('users')->nullOnDelete();
            });

            foreach (DB::table('devices')->whereNotNull('employee_id')->get() as $device) {
                if (isset($employeeIdToUserId[$device->employee_id])) {
                    DB::table('devices')->where('id', $device->id)
                        ->update(['user_id' => $employeeIdToUserId[$device->employee_id]]);
                }
            }

            Schema::table('devices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('employee_id');
            });
        }

        if (Schema::hasColumn('activity_logs', 'employee_id')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('employee_id')
                    ->constrained('users')->nullOnDelete();
            });

            foreach (DB::table('activity_logs')->whereNotNull('employee_id')->get() as $log) {
                if (isset($employeeIdToUserId[$log->employee_id])) {
                    DB::table('activity_logs')->where('id', $log->id)
                        ->update(['user_id' => $employeeIdToUserId[$log->employee_id]]);
                }
            }

            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('employee_id');
            });
        }

        // SubscriptionAllocation is polymorphic (assignable_type/assignable_id);
        // rows pointing at App\Models\Employee are repointed at App\Models\User
        // using the same id map.
        foreach (DB::table('subscription_allocations')->where('assignable_type', 'App\\Models\\Employee')->get() as $allocation) {
            if (isset($employeeIdToUserId[$allocation->assignable_id])) {
                DB::table('subscription_allocations')->where('id', $allocation->id)->update([
                    'assignable_type' => 'App\\Models\\User',
                    'assignable_id' => $employeeIdToUserId[$allocation->assignable_id],
                ]);
            }
        }

        Schema::dropIfExists('employees');
    }

    public function down(): void
    {
        // Irreversible: the employees table and old employee_id FKs are gone.
    }
};
