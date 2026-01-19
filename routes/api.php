<?php

use Illuminate\Support\Facades\Route;

// Test route to verify Laravel is working
Route::get('/test', function () {
    return response()->json(['status' => 'ok', 'message' => 'Laravel is working']);
});

// Authentication routes are handled by the Periscope\AuthModule package
// See src/AuthModule/src/Providers/AuthModuleServiceProvider.php
