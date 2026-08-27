<?php

namespace App\Providers;

use App\ClientOps\Models\Client;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */

public function boot()
{
    Paginator::useBootstrapFive();

    // "Account manager" is a department (job function), not a Spatie role —
    // access to Client Ops is granted by department membership or by an
    // access-level role (Finance/HR Admin/Super Admin) that oversees it.
    Gate::define('access-client-ops', function ($user) {
        return $user->isInDepartment('Account Manager')
            || $user->hasAnyRole(['Finance', 'HR Admin', 'Super Admin']);
    });

    // Finance/HR Admin/Super Admin and the Account Manager department's
    // Head can see every client, but only Finance/HR Admin/Super Admin
    // (and the owning account manager) can edit one — the Head's view-all
    // is read-only, matching how they oversee without taking over accounts.
    Gate::define('view-client', function ($user, Client $client) {
        return $user->hasAnyRole(['Finance', 'HR Admin', 'Super Admin'])
            || $user->isHeadOfDepartment('Account Manager')
            || $client->isOwnedBy($user);
    });

    Gate::define('manage-client', function ($user, Client $client) {
        return $user->hasAnyRole(['Finance', 'HR Admin', 'Super Admin'])
            || $client->isOwnedBy($user);
    });
}
}
