<?php

namespace App\Providers;

use App\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        $this->app->when(UserRepository::class)
            ->needs('$modelClass')
            ->give(fn () => config('auth.providers.users.model', \App\Models\User::class));
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
