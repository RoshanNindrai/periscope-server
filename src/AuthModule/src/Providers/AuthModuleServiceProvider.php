<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Periscope\AuthModule\Constants\AuthModuleConstants;
use Periscope\AuthModule\Contracts\PhoneHasherInterface;
use Periscope\AuthModule\Contracts\VerificationCodeGeneratorInterface;
use Periscope\AuthModule\Services\LoginOtpService;
use Periscope\AuthModule\Services\PhoneVerificationService;
use Periscope\AuthModule\Services\RegistrationService;
use Periscope\AuthModule\Support\PhoneHasher;
use Periscope\AuthModule\Support\VerificationCodeGenerator;
use Periscope\AuthModule\Support\VerificationCodeRepositoryFactory;

class AuthModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/auth-module.php',
            AuthModuleConstants::CONFIG_KEY
        );

        $this->registerContracts();
        $this->registerServices();
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/auth-module.php' => config_path('auth-module.php'),
        ], 'auth-module-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'auth-module-migrations');

        $this->app->make(\Illuminate\Notifications\ChannelManager::class)->extend('sms', function ($app) {
            return new \Periscope\AuthModule\Notifications\Channels\SmsChannel();
        });

        $this->loadRoutes();
    }

    private function registerContracts(): void
    {
        $this->app->bind(PhoneHasherInterface::class, PhoneHasher::class);
        $this->app->bind(VerificationCodeGeneratorInterface::class, VerificationCodeGenerator::class);
        $this->app->singleton(VerificationCodeRepositoryFactory::class);
    }

    private function registerServices(): void
    {
        $tokenName = fn () => config(AuthModuleConstants::CONFIG_TOKEN_NAME, 'periscope-auth-token');

        $this->app->when(RegistrationService::class)->needs('$tokenName')->give($tokenName);
        $this->app->when(LoginOtpService::class)->needs('$tokenName')->give($tokenName);
    }

    protected function loadRoutes(): void
    {
        $prefix = config(AuthModuleConstants::CONFIG_ROUTE_PREFIX, 'api');
        $middleware = config(AuthModuleConstants::CONFIG_ROUTE_MIDDLEWARE, ['api']);
        $rateLimits = config(AuthModuleConstants::CONFIG_RATE_LIMITS, []);

        Route::middleware($middleware)
            ->prefix($prefix)
            ->name('auth.')
            ->group(function () use ($rateLimits) {
                Route::get('/health', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'healthCheck'])
                    ->name('health');

                $registerLimit = $rateLimits['register'] ?? '5,1';
                $loginLimit = $rateLimits['login'] ?? '5,1';
                $verifyLoginLimit = $rateLimits['verify_login'] ?? '5,1';
                $verifyPhoneLimit = $rateLimits['verify_phone'] ?? '5,1';
                $resendVerificationLimit = $rateLimits['resend_verification'] ?? '3,1';

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

                Route::middleware('auth:sanctum')->group(function () use ($resendVerificationLimit) {
                    Route::post('/logout', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'logout'])
                        ->name('logout');
                    Route::get('/me', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'me'])
                        ->name('me');
                    Route::middleware("throttle:{$resendVerificationLimit}")->group(function () {
                        Route::post('/resend-verification-sms', [\Periscope\AuthModule\Http\Controllers\AuthController::class, 'resendVerificationSms'])
                            ->name('resend-verification-sms');
                    });
                });
            });
    }
}
