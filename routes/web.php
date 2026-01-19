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
        ],
    ]);
});
