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
    ->withCommands([\App\Console\Commands\ProductionCheck::class])
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
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_AWS_ELB
        );
        $middleware->trustHosts(at: function () {
            $hosts = array_merge(
                [(string) parse_url(config('app.url'), PHP_URL_HOST)],
                config('deployment.hosts', [])
            );

            return array_map(fn ($host) => '^'.preg_quote($host, '/').'$', array_filter($hosts));
        }, subdomains: false);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
