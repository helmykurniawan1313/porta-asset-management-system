<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/performance-review.php'));
            Route::middleware('web')->group(base_path('routes/brand-health.php'));
        },
    )
    // Brand Health's artisan commands live outside app/Console/Commands.
    ->withCommands([__DIR__.'/../app/BrandHealth/Console/Commands'])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            return Redirect::to($request->headers->get('referer') ?? route('login'))
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('status', 'Your session had expired, please try again.');
        });
    })->create();
