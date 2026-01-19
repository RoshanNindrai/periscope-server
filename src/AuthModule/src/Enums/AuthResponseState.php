<?php

namespace Periscope\AuthModule\Enums;

enum AuthResponseState: string
{
    case REGISTERED = 'REGISTERED';
    case LOGGED_IN = 'LOGGED_IN';
    case LOGGED_OUT = 'LOGGED_OUT';
    case PASSWORD_RESET_LINK_SENT = 'PASSWORD_RESET_LINK_SENT';
    case PASSWORD_RESET = 'PASSWORD_RESET';
    case EMAIL_VERIFIED = 'EMAIL_VERIFIED';
    case EMAIL_ALREADY_VERIFIED = 'EMAIL_ALREADY_VERIFIED';
    case VERIFICATION_EMAIL_SENT = 'VERIFICATION_EMAIL_SENT';
    case ACCOUNT_LOCKED = 'ACCOUNT_LOCKED';
    case ACCOUNT_ALREADY_LOCKED = 'ACCOUNT_ALREADY_LOCKED';
    case USER_RETRIEVED = 'USER_RETRIEVED';
    case HEALTH_CHECK = 'HEALTH_CHECK';

    /**
     * Get human-readable message for the state
     */
    public function message(): string
    {
        return match ($this) {
            self::REGISTERED => 'User registered successfully. Please check your email to verify your account.',
            self::LOGGED_IN => 'Login successful',
            self::LOGGED_OUT => 'Logged out successfully',
            self::PASSWORD_RESET_LINK_SENT => 'If an account exists with that email, a password reset link has been sent.',
            self::PASSWORD_RESET => 'Password has been reset successfully. Please check your email to confirm this action.',
            self::EMAIL_VERIFIED => 'Email verified successfully.',
            self::EMAIL_ALREADY_VERIFIED => 'Email already verified.',
            self::VERIFICATION_EMAIL_SENT => 'Verification email has been sent.',
            self::ACCOUNT_LOCKED => 'Your account has been locked for security. Please contact support to unlock it.',
            self::ACCOUNT_ALREADY_LOCKED => 'Account is already locked.',
            self::USER_RETRIEVED => 'User retrieved successfully.',
            self::HEALTH_CHECK => 'Server is healthy and running.',
        };
    }
}
