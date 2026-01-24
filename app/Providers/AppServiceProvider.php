<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        // Production: force APP_DEBUG false regardless of .env
        if ($this->app->environment('production')) {
            config(['app.debug' => false]);
        }

        // Trust proxy + HTTPS: App\Http\Middleware\TrustProxiesAndHttps (prepended to api group)
    }
}
