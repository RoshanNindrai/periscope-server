<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Feature keys for staging magic bypass. Add a constant when introducing a new
 * bypass; then add the corresponding entry in config/staging-bypass.php.
 */
final class StagingBypassFeature
{
    private function __construct() {}

    public const LOGIN_OTP = 'login_otp';

    public const PHONE_VERIFICATION = 'phone_verification';
}
