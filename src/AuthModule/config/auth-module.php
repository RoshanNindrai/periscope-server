<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This is the Eloquent model that should be used for authentication.
    | The model must use Laravel\Sanctum\HasApiTokens trait.
    |
    */
    'user_model' => env('AUTH_MODULE_USER_MODEL', \App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Frontend URL
    |--------------------------------------------------------------------------
    |
    | This is the URL of your frontend application. It's used in account
    | lock emails to generate the lock account link.
    |
    */
    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost')),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for all authentication routes. Default is 'api'.
    |
    */
    'route_prefix' => env('AUTH_MODULE_ROUTE_PREFIX', 'api'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware to apply to all authentication routes.
    |
    */
    'route_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration for authentication endpoints.
    | Format: 'max_attempts,decay_minutes'
    |
    */
    'rate_limits' => [
        'register' => env('AUTH_MODULE_RATE_LIMIT_REGISTER', '5,1'), // 5 attempts per minute
        'login' => env('AUTH_MODULE_RATE_LIMIT_LOGIN', '5,1'), // 5 attempts per minute (send OTP)
        'verify_login' => env('AUTH_MODULE_RATE_LIMIT_VERIFY_LOGIN', '5,1'), // 5 attempts per minute (verify OTP)
        'resend_verification' => env('AUTH_MODULE_RATE_LIMIT_RESEND_VERIFICATION', '3,1'), // 3 attempts per minute
        'verify_phone' => env('AUTH_MODULE_RATE_LIMIT_VERIFY_PHONE', '5,1'), // 5 attempts per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Name
    |--------------------------------------------------------------------------
    |
    | The name for API tokens created by this module.
    |
    */
    'token_name' => env('AUTH_MODULE_TOKEN_NAME', 'periscope-auth-token'),

    /*
    |--------------------------------------------------------------------------
    | Password Requirements
    |--------------------------------------------------------------------------
    |
    | Password validation rules. Can be customized per application needs.
    |
    */
    'password_min_length' => env('AUTH_MODULE_PASSWORD_MIN_LENGTH', 8),
    'password_require_uppercase' => env('AUTH_MODULE_PASSWORD_REQUIRE_UPPERCASE', false),
    'password_require_lowercase' => env('AUTH_MODULE_PASSWORD_REQUIRE_LOWERCASE', false),
    'password_require_numbers' => env('AUTH_MODULE_PASSWORD_REQUIRE_NUMBERS', false),
    'password_require_symbols' => env('AUTH_MODULE_PASSWORD_REQUIRE_SYMBOLS', false),

    /*
    |--------------------------------------------------------------------------
    | AWS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AWS services (SNS for SMS delivery)
    |
    */
    'aws' => [
        'sns' => [
            'region' => env('AWS_SNS_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
    ],
];
