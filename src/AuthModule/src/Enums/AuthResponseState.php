<?php

namespace Periscope\AuthModule\Enums;

enum AuthResponseState: string
{
    case REGISTERED = 'REGISTERED';
    case LOGIN_CODE_SENT = 'LOGIN_CODE_SENT';
    case LOGGED_IN = 'LOGGED_IN';
    case LOGGED_OUT = 'LOGGED_OUT';
    case PHONE_VERIFIED = 'PHONE_VERIFIED';
    case PHONE_ALREADY_VERIFIED = 'PHONE_ALREADY_VERIFIED';
    case VERIFICATION_SMS_SENT = 'VERIFICATION_SMS_SENT';
    case USER_RETRIEVED = 'USER_RETRIEVED';
    case HEALTH_CHECK = 'HEALTH_CHECK';

    /**
     * Get human-readable message for the state
     */
    public function message(): string
    {
        return match ($this) {
            self::REGISTERED => 'User registered successfully. Please check your phone for a verification code.',
            self::LOGIN_CODE_SENT => 'Login code has been sent to your phone.',
            self::LOGGED_IN => 'Login successful.',
            self::LOGGED_OUT => 'Logged out successfully.',
            self::PHONE_VERIFIED => 'Phone verified successfully.',
            self::PHONE_ALREADY_VERIFIED => 'Phone number is already verified.',
            self::VERIFICATION_SMS_SENT => 'Verification SMS has been sent.',
            self::USER_RETRIEVED => 'User retrieved successfully.',
            self::HEALTH_CHECK => 'Server is healthy and running.',
        };
    }
}
