<?php

use App\Http\Middleware\Localization;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Auth\AuthenticationException;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->use([
            Localization::class,
            HandleCors::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'language' => Localization::class,
            'client' => EnsureClientIsResourceOwner::class,
            'ensure.company.active' => \App\Http\Middleware\EnsureCompanyIsActive::class,
        ]);
    })
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('auth:clear-resets')->everyTwoHours();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => __('auth.unauthenticated'),
                ], 401);
            }
        });
        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 403 && ($request->expectsJson() || $request->is('api/*'))) {
                return response()->json([
                    'message' => __('auth.forbidden'),
                ], 403);
            }
        });
    })->create();
