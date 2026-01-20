<?php

namespace Periscope\SearchModule\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class SearchModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/search-module.php',
            'search-module'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/search-module.php' => config_path('search-module.php'),
        ], 'search-module-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'search-module-migrations');

        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        $prefix = config('search-module.route_prefix', 'api');
        $middleware = config('search-module.route_middleware', ['api', 'auth:sanctum']);
        $rateLimit = config('search-module.rate_limit', '30,1');

        Route::middleware($middleware)
            ->prefix($prefix)
            ->name('search.')
            ->group(function () use ($rateLimit) {
                Route::middleware("throttle:{$rateLimit}")->group(function () {
                    Route::get('/users/search', [\Periscope\SearchModule\Http\Controllers\SearchController::class, 'searchUsers'])
                        ->name('users');
                });
            });
    }
}
