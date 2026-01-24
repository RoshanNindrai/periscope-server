<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Constants;

/**
 * Centralized constants for the Auth module.
 * Config keys, table names, and domain invariants.
 */
final class AuthModuleConstants
{
    private function __construct() {}

    // --- Config keys
    public const CONFIG_KEY = 'auth-module';
    public const CONFIG_USER_MODEL = self::CONFIG_KEY . '.user_model';
    public const CONFIG_TOKEN_NAME = self::CONFIG_KEY . '.token_name';
    public const CONFIG_ROUTE_PREFIX = self::CONFIG_KEY . '.route_prefix';
    public const CONFIG_ROUTE_MIDDLEWARE = self::CONFIG_KEY . '.route_middleware';
    public const CONFIG_RATE_LIMITS = self::CONFIG_KEY . '.rate_limits';

    // --- Table names
    public const TABLE_PHONE_VERIFICATION_CODES = 'phone_verification_codes';
    public const TABLE_LOGIN_VERIFICATION_CODES = 'login_verification_codes';

    // --- Verification code rules
    public const CODE_LENGTH = 6;
    public const CODE_EXPIRY_MINUTES = 10;
    public const MAX_VERIFICATION_ATTEMPTS = 5;

    // --- Phone masking (for logging)
    public const PHONE_MASK_REVEALED_DIGITS = 4;

    // --- Hash
    public const PHONE_HASH_ALGO = 'sha256';
}
