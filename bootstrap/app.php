<?php

use App\Http\Middleware\HomeRedirect;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use RealRashid\SweetAlert\ToSweetAlert;
use Spatie\Honeypot\ProtectAgainstSpam;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        using: function () {
            Route::middleware('api')
                ->prefix('api')
                ->name('api.')
                ->group(base_path('routes/dmiesys/api.php'));

            Route::middleware(['web', 'throttle:20,1', ProtectAgainstSpam::class])
                ->group(base_path('routes/web.php'));

            Route::middleware(['web', 'throttle:20,1', HomeRedirect::class, ProtectAgainstSpam::class])
                ->name('home.')
                ->prefix('pages')
                ->group(base_path('routes/home/web.php'));

            Route::middleware(['web', 'auth', 'throttle:50,1', ProtectAgainstSpam::class])
                ->prefix('dmiesys')
                ->group(base_path('routes/dmiesys/web.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            ToSweetAlert::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
    })->create();
