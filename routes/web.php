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
            'logout' => '/api/logout',
            'me' => '/api/me',
            'search_users' => '/api/users/search?q={term}',
            'verify_email' => '/api/verify-email',
            'forgot_password' => '/api/forgot-password',
            'reset_password' => '/api/reset-password',
        ],
        'documentation' => 'See Postman collections in /docs/postman/',
    ]);
});
