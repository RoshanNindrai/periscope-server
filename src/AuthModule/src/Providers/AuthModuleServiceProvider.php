<?php

namespace Periscope\AuthModule\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AuthModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/auth-module.php',
            'auth-module'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/auth-module.php' => config_path('auth-module.php'),
        ], 'auth-module-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'auth-module-migrations');

        // Register custom SMS notification channel
        $this->app->make(\Illuminate\Notifications\ChannelManager::class)->extend('sms', function ($app) {
            return new \Periscope\AuthModule\Notifications\Channels\SmsChannel();
        });

        $this->loadRoutes();
    }

    /**
     * Load the package routes.
     */
    protected function loadRoutes(): void
    {
        $prefix = config('auth-module.route_prefix', 'api');
        $middleware = config('auth-module.route_middleware', ['api']);

        Route::middleware($middleware)
            ->prefix($prefix)
            ->name('auth.')
            ->group(function () {
                // Health check endpoint
                Route::get('/health', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'healthCheck'])
                    ->name('health');
                
                // Rate limit sensitive endpoints
                $registerLimit = config('auth-module.rate_limits.register', '5,1');
                $loginLimit = config('auth-module.rate_limits.login', '5,1');
                $verifyLoginLimit = config('auth-module.rate_limits.verify_login', '5,1');
                $verifyPhoneLimit = config('auth-module.rate_limits.verify_phone', '5,1');
                
                Route::middleware("throttle:{$registerLimit}")->group(function () {
                    Route::post('/register', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'register'])
                        ->name('register');
                });
                
                Route::middleware("throttle:{$loginLimit}")->group(function () {
                    Route::post('/login', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'login'])
                        ->name('login');
                });
                
                Route::middleware("throttle:{$verifyLoginLimit}")->group(function () {
                    Route::post('/verify-login', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'verifyLogin'])
                        ->name('verify-login');
                });

                Route::middleware("throttle:{$verifyPhoneLimit}")->group(function () {
                    Route::post('/verify-phone', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'verifyPhone'])
                        ->name('verify-phone');
                });

                Route::middleware('auth:sanctum')->group(function () {
                    Route::post('/logout', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'logout'])
                        ->name('logout');
                    Route::get('/me', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'me'])
                        ->name('me');
                    
                    // Rate limit resend verification
                    $resendVerificationLimit = config('auth-module.rate_limits.resend_verification', '3,1');
                    Route::middleware("throttle:{$resendVerificationLimit}")->group(function () {
                        Route::post('/resend-verification-sms', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'resendVerificationSms'])
                            ->name('resend-verification-sms');
                    });
                });
            });
    }
}
