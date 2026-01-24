<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [\App\Http\Middleware\TrustProxiesAndHttps::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('graphql')) {
                return \App\Support\Http\ApiResponse::error(\Periscope\AuthModule\Enums\AuthErrorCode::UNAUTHORIZED);
            }
            return null;
        });

        $exceptions->render(function (\Illuminate\Auth\AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('graphql')) {
                return \App\Support\Http\ApiResponse::error(\Periscope\AuthModule\Enums\AuthErrorCode::FORBIDDEN);
            }
            return null;
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('graphql')) {
                return \App\Support\Http\ApiResponse::error(\Periscope\AuthModule\Enums\AuthErrorCode::RATE_LIMIT_EXCEEDED);
            }
            return null;
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('graphql')) {
                return \App\Support\Http\ApiResponse::error(\Periscope\AuthModule\Enums\AuthErrorCode::VALIDATION_ERROR, $e->errors());
            }
            return null;
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('graphql')) {
                return \App\Support\Http\ApiResponse::error(\Periscope\AuthModule\Enums\AuthErrorCode::NOT_FOUND);
            }
            return null;
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('graphql')) {
                return \App\Support\Http\ApiResponse::error(\Periscope\AuthModule\Enums\AuthErrorCode::NOT_FOUND);
            }
            return null;
        });
    })->create();
