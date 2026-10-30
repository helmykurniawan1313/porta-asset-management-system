<?php

namespace App\Providers;

use App\ClientOps\Models\Client;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;


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

    // When the site is served over HTTPS (APP_URL https://...), always build https links,
    // even if a proxy header is missing; an http:// fetch from an https page is blocked.
    if (str_starts_with((string) config('app.url'), 'https://')) {
        URL::forceScheme('https');
    }

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

    // Brand Health editing mirrors Brand Matrix's "editor" role: Super Admins
    // plus the Accounts department (incl. Accounts Manager), who own client data.
    Gate::define('edit-brand-health', function ($user) {
        return $user->hasRole('Super Admin') || $user->isInDepartment('Accounts');
    });

    // Brand Matrix's super-admin-only pages (site settings, report prompt).
    Gate::define('admin-brand-health', fn ($user) => $user->hasRole('Super Admin'));

    // Every Brand Health screen colours status badges from the label buckets
    // (Brand Matrix shared these app-wide); load them once per request.
    View::composer('brand-health.*', function ($view) {
        static $buckets = null;
        $buckets ??= \App\BrandHealth\Models\BrandLabelBucket::get(['id', 'metric', 'min_score', 'label', 'color', 'custom_color_bg', 'custom_color_ink']);
        $view->with('bhLabelBuckets', $buckets);
    });
}
}
