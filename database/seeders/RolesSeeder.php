<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Creates the four roles used by the performance-review module (Employee,
 * Manager, HR Admin, Super Admin) and promotes the first existing user to
 * Super Admin so the app is usable immediately after a fresh migrate.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Employee', 'Manager', 'HR Admin', 'IT Admin', 'Super Admin'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $firstUser = User::orderBy('id')->first();

        if ($firstUser && ! $firstUser->hasAnyRole(['Employee', 'Manager', 'HR Admin', 'IT Admin', 'Super Admin'])) {
            $firstUser->assignRole('Super Admin');
        }
    }
}
