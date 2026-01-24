<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Periscope API Server',
        'version' => '1.0.0',
        'endpoints' => [
            'health' => '/api/health',
            'register' => '/api/register',
            'login' => '/api/login',
            'verify_login' => '/api/verify-login',
            'logout' => '/api/logout',
            'me' => '/api/me',
            'verify_phone' => '/api/verify-phone',
            'resend_verification_sms' => '/api/resend-verification-sms',
            'graphql' => 'POST /graphql',
        ],
        'documentation' => 'See /docs/postman/Periscope.postman_collection.json',
    ]);
});
