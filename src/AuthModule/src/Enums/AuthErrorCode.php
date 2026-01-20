<?php

namespace Periscope\AuthModule\Enums;

enum AuthErrorCode: string
{
    // Registration errors
    case REGISTRATION_FAILED = 'REGISTRATION_FAILED';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    
    // Login errors
    case LOGIN_FAILED = 'LOGIN_FAILED';
    case ACCOUNT_LOCKED = 'ACCOUNT_LOCKED';
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    
    // Logout errors
    case LOGOUT_FAILED = 'LOGOUT_FAILED';
    
    // User retrieval errors
    case USER_RETRIEVAL_FAILED = 'USER_RETRIEVAL_FAILED';
    
    // Password reset errors
    case PASSWORD_RESET_FAILED = 'PASSWORD_RESET_FAILED';
    case INVALID_RESET_TOKEN = 'INVALID_RESET_TOKEN';
    case EXPIRED_RESET_TOKEN = 'EXPIRED_RESET_TOKEN';
    case INVALID_RESET_CODE = 'INVALID_RESET_CODE';
    case EXPIRED_RESET_CODE = 'EXPIRED_RESET_CODE';
    case MAX_RESET_ATTEMPTS = 'MAX_RESET_ATTEMPTS';
    case INVALID_USER = 'INVALID_USER';
    
    // Email verification errors
    case EMAIL_VERIFICATION_FAILED = 'EMAIL_VERIFICATION_FAILED';
    case INVALID_VERIFICATION_CODE = 'INVALID_VERIFICATION_CODE';
    case EXPIRED_VERIFICATION_CODE = 'EXPIRED_VERIFICATION_CODE';
    case MAX_VERIFICATION_ATTEMPTS = 'MAX_VERIFICATION_ATTEMPTS';
    case VERIFICATION_EMAIL_SEND_FAILED = 'VERIFICATION_EMAIL_SEND_FAILED';
    
    // Account locking errors
    case ACCOUNT_LOCK_FAILED = 'ACCOUNT_LOCK_FAILED';
    case INVALID_LOCK_SIGNATURE = 'INVALID_LOCK_SIGNATURE';
    case EXPIRED_LOCK_LINK = 'EXPIRED_LOCK_LINK';
    case USER_NOT_FOUND = 'USER_NOT_FOUND';
    case UNABLE_TO_LOCK_ACCOUNT = 'UNABLE_TO_LOCK_ACCOUNT';
    case UNABLE_TO_VERIFY_EMAIL = 'UNABLE_TO_VERIFY_EMAIL';

    /**
     * Get human-readable message for the error
     */
    public function message(): string
    {
        return match ($this) {
            self::REGISTRATION_FAILED => 'Registration failed. Please try again later.',
            self::LOGIN_FAILED => 'Login failed. Please try again later.',
            self::ACCOUNT_LOCKED => 'Your account has been locked. Please contact support.',
            self::INVALID_CREDENTIALS => 'The provided credentials are incorrect.',
            self::LOGOUT_FAILED => 'Logout failed. Please try again.',
            self::USER_RETRIEVAL_FAILED => 'Unable to retrieve user information.',
            self::PASSWORD_RESET_FAILED => 'Password reset failed. Please try again later.',
            self::INVALID_RESET_TOKEN => 'Invalid or expired reset token.',
            self::EXPIRED_RESET_TOKEN => 'Reset token has expired.',
            self::INVALID_RESET_CODE => 'Invalid or incorrect reset code.',
            self::EXPIRED_RESET_CODE => 'Reset code has expired (10 minutes).',
            self::MAX_RESET_ATTEMPTS => 'Too many failed attempts. Please request a new reset code.',
            self::INVALID_USER => 'Invalid user.',
            self::EMAIL_VERIFICATION_FAILED => 'Email verification failed. Please try again later.',
            self::INVALID_VERIFICATION_CODE => 'Invalid or incorrect verification code.',
            self::EXPIRED_VERIFICATION_CODE => 'Verification code has expired (10 minutes).',
            self::MAX_VERIFICATION_ATTEMPTS => 'Too many failed attempts. Please request a new verification code.',
            self::VERIFICATION_EMAIL_SEND_FAILED => 'Failed to send verification email. Please try again later.',
            self::ACCOUNT_LOCK_FAILED => 'Account lock failed. Please try again later.',
            self::INVALID_LOCK_SIGNATURE => 'Invalid lock account link signature.',
            self::EXPIRED_LOCK_LINK => 'Lock account link has expired.',
            self::USER_NOT_FOUND => 'User not found.',
            self::UNABLE_TO_LOCK_ACCOUNT => 'Unable to lock account.',
            self::UNABLE_TO_VERIFY_EMAIL => 'Unable to verify email.',
            self::VALIDATION_ERROR => 'The given data was invalid.',
        };
    }

    /**
     * Get HTTP status code for the error
     */
    public function statusCode(): int
    {
        return match ($this) {
            self::ACCOUNT_LOCKED => 403,
            self::USER_NOT_FOUND => 404,
            self::INVALID_RESET_TOKEN,
            self::EXPIRED_RESET_TOKEN,
            self::INVALID_RESET_CODE,
            self::EXPIRED_RESET_CODE,
            self::MAX_RESET_ATTEMPTS,
            self::INVALID_USER,
            self::INVALID_VERIFICATION_CODE,
            self::EXPIRED_VERIFICATION_CODE,
            self::MAX_VERIFICATION_ATTEMPTS,
            self::INVALID_LOCK_SIGNATURE,
            self::EXPIRED_LOCK_LINK,
            self::VALIDATION_ERROR => 422,
            default => 500,
        };
    }
}
