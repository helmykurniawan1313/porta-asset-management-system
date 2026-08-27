<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds the requested department structure with placeholder users in each.
 * Departments are matched by name if they already exist (so this is safe to
 * re-run), and only missing users are topped up to reach the target count
 * per department — existing users in a department are left alone and count
 * toward the target.
 */
class OrgStructureSeeder extends Seeder
{
    public function run(): void
    {
        $flatDepartments = [
            'Design' => 10,
            'Ads' => 4,
            'Content Creator' => 10,
            'Video & Photo' => 8,
            'Account Manager' => 10,
            'Content Writer' => 5,
        ];

        foreach ($flatDepartments as $name => $count) {
            $department = Department::firstOrCreate(['name' => $name]);
            $this->topUpUsers($department, $count);
        }

        $ops = Department::firstOrCreate(['name' => 'Ops']);

        $subDepartments = [
            'Finance' => 2,
            'HR' => 2,
            'IT' => 2,
        ];

        foreach ($subDepartments as $name => $count) {
            $department = Department::firstOrCreate(['name' => $name], ['parent_id' => $ops->id]);

            if (! $department->parent_id) {
                $department->update(['parent_id' => $ops->id]);
            }

            $this->topUpUsers($department, $count);
        }
    }

    private function topUpUsers(Department $department, int $targetCount): void
    {
        $existing = User::where('department_id', $department->id)->count();

        if ($existing >= $targetCount) {
            return;
        }

        $slug = Str::slug($department->name);

        for ($i = $existing + 1; $i <= $targetCount; $i++) {
            $email = "{$slug}-{$i}@porta.test";

            if (User::where('email', $email)->exists()) {
                continue;
            }

            User::create([
                'name' => $department->name.' User '.$i,
                'email' => $email,
                'password' => Hash::make('password'),
                'department_id' => $department->id,
            ]);
        }
    }
}
