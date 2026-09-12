<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PermissionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Force HTTPS URL generation in production (the app sits behind a
        // reverse proxy; forwarded headers are trusted in bootstrap/app.php).
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Route every Gate ability through the YPA permission engine.
        Gate::before(function ($user, $ability) {
            if (!is_string($ability)) {
                return null; // let policies handle model abilities
            }

            return app(PermissionService::class)->can($ability, $user);
        });

        // Share site settings (name / logo / favicon) with every view.
        View::composer('*', function ($view) {
            try {
                $siteName = Setting::value('site_name', 'Youth Platform Africa');
                $siteLogo = Setting::asset('site_logo', '');
                $siteFavicon = Setting::asset('site_favicon', '');
            } catch (\Throwable $e) {
                $siteName = 'Youth Platform Africa';
                $siteLogo = '';
                $siteFavicon = '';
            }

            $view->with(compact('siteName', 'siteLogo', 'siteFavicon'));
        });
    }
}
