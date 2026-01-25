<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Staging Magic Bypass
    |--------------------------------------------------------------------------
    |
    | When APP_ENV=staging, each feature can have a magic value that bypasses
    | normal verification. Set only in staging .env. Do not set in production.
    |
    | Values are read from env() here so they are baked into config when
    | config:cache runs. Using env() in app code would return null when the
    | config cache is used (Laravel does not load .env when config is cached).
    |
    | To add a new feature:
    | 1. Add a constant in App\Support\StagingBypassFeature.
    | 2. Add an entry here: key = constant value, value = env('YOUR_BYPASS_ENV_VAR').
    | 3. In your service, inject StagingMagicBypassInterface and call
    |    $this->stagingBypass->allows(StagingBypassFeature::YOUR_FEATURE, $value).
    |
    */
    'features' => [
        'login_otp' => env('AUTH_OTP_BYPASS_MAGIC'),
        'phone_verification' => env('AUTH_PHONE_VERIFICATION_BYPASS_MAGIC'),
    ],
];
