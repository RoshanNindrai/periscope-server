<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Trust proxy headers from AWS load balancer
        // This allows Laravel to detect HTTPS correctly when behind a load balancer
        if ($this->app->environment('production')) {
            $this->app['request']->server->set('HTTPS', 'on');
            
            // Trust all proxies (AWS load balancer)
            // In production, you can be more specific with trusted proxies
            \Illuminate\Http\Request::setTrustedProxies(
                ['*'],
                \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
            );
            
            URL::forceScheme('https');
        }
    }
}
