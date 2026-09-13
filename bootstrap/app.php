<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectUsersTo(fn () => route('home'));

        $middleware->alias([
            'user.status' => \App\Http\Middleware\CheckUserStatus::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);

        // The app sits behind a reverse proxy (nginx). Trust only the proxy
        // IPs listed in TRUSTED_PROXIES (comma-separated) so forwarded headers
        // are honoured for URL::forceScheme('https') and isSecure(). Leave
        // TRUSTED_PROXIES empty when the app is not behind a proxy — no
        // proxies are trusted then.
        $trustedProxies = trim((string) env('TRUSTED_PROXIES', ''));
        $middleware->trustProxies(
            at: $trustedProxies === '' ? [] : array_map('trim', explode(',', $trustedProxies)),
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_AWS_ELB
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
