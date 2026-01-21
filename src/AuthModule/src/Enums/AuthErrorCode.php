<?php

namespace Periscope\AuthModule\Enums;

enum AuthErrorCode: string
{
    // Registration errors
    case REGISTRATION_FAILED = 'REGISTRATION_FAILED';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    
    // Login errors
    case LOGIN_FAILED = 'LOGIN_FAILED';
    case INVALID_PHONE = 'INVALID_PHONE';
    case LOGIN_CODE_SEND_FAILED = 'LOGIN_CODE_SEND_FAILED';
    case INVALID_LOGIN_CODE = 'INVALID_LOGIN_CODE';
    case EXPIRED_LOGIN_CODE = 'EXPIRED_LOGIN_CODE';
    case MAX_LOGIN_ATTEMPTS = 'MAX_LOGIN_ATTEMPTS';
    
    // Logout errors
    case LOGOUT_FAILED = 'LOGOUT_FAILED';
    
    // User retrieval errors
    case USER_RETRIEVAL_FAILED = 'USER_RETRIEVAL_FAILED';
    case USER_NOT_FOUND = 'USER_NOT_FOUND';
    
    // Phone verification errors
    case PHONE_VERIFICATION_FAILED = 'PHONE_VERIFICATION_FAILED';
    case INVALID_VERIFICATION_CODE = 'INVALID_VERIFICATION_CODE';
    case EXPIRED_VERIFICATION_CODE = 'EXPIRED_VERIFICATION_CODE';
    case MAX_VERIFICATION_ATTEMPTS = 'MAX_VERIFICATION_ATTEMPTS';
    case VERIFICATION_SMS_SEND_FAILED = 'VERIFICATION_SMS_SEND_FAILED';
    case UNABLE_TO_VERIFY_PHONE = 'UNABLE_TO_VERIFY_PHONE';

    /**
     * Get human-readable message for the error
     */
    public function message(): string
    {
        return match ($this) {
            self::REGISTRATION_FAILED => 'Registration failed. Please try again later.',
            self::LOGIN_FAILED => 'Login failed. Please try again later.',
            self::INVALID_PHONE => 'The provided phone number is invalid.',
            self::LOGIN_CODE_SEND_FAILED => 'Failed to send login code. Please try again later.',
            self::INVALID_LOGIN_CODE => 'Invalid or incorrect login code.',
            self::EXPIRED_LOGIN_CODE => 'Login code has expired (10 minutes).',
            self::MAX_LOGIN_ATTEMPTS => 'Too many failed attempts. Please request a new login code.',
            self::LOGOUT_FAILED => 'Logout failed. Please try again.',
            self::USER_RETRIEVAL_FAILED => 'Unable to retrieve user information.',
            self::USER_NOT_FOUND => 'User not found.',
            self::PHONE_VERIFICATION_FAILED => 'Phone verification failed. Please try again later.',
            self::INVALID_VERIFICATION_CODE => 'Invalid or incorrect verification code.',
            self::EXPIRED_VERIFICATION_CODE => 'Verification code has expired (10 minutes).',
            self::MAX_VERIFICATION_ATTEMPTS => 'Too many failed attempts. Please request a new verification code.',
            self::VERIFICATION_SMS_SEND_FAILED => 'Failed to send verification SMS. Please try again later.',
            self::UNABLE_TO_VERIFY_PHONE => 'Unable to verify phone number.',
            self::VALIDATION_ERROR => 'The given data was invalid.',
        };
    }

    /**
     * Get HTTP status code for the error
     */
    public function statusCode(): int
    {
        return match ($this) {
            self::USER_NOT_FOUND => 404,
            self::INVALID_PHONE,
            self::INVALID_LOGIN_CODE,
            self::EXPIRED_LOGIN_CODE,
            self::MAX_LOGIN_ATTEMPTS,
            self::INVALID_VERIFICATION_CODE,
            self::EXPIRED_VERIFICATION_CODE,
            self::MAX_VERIFICATION_ATTEMPTS,
            self::VALIDATION_ERROR => 422,
            default => 500,
        };
    }
}
