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
    | To add a new feature:
    | 1. Add a constant in App\Support\StagingBypassFeature.
    | 2. Add an entry here (key must match the constant); value is the env var name.
    | 3. In your service, inject StagingMagicBypassInterface and call
    |    $this->stagingBypass->allows(StagingBypassFeature::YOUR_FEATURE, $value).
    |
    | The magic is read from env() at runtime so it works with config:cache.
    |
    */
    'features' => [
        'login_otp' => 'AUTH_OTP_BYPASS_MAGIC',
        'phone_verification' => 'AUTH_PHONE_VERIFICATION_BYPASS_MAGIC',
    ],
];
