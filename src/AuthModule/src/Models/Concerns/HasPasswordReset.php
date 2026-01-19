<?php

namespace Periscope\AuthModule\Models\Concerns;

use Periscope\AuthModule\Notifications\ResetPasswordNotification;
use Periscope\AuthModule\Notifications\VerifyEmailNotification;

trait HasPasswordReset
{
    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    /**
     * Get the email address that should be used for verification.
     */
    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    /**
     * Determine if the user has verified their email address.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark the given user's email as verified.
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Determine if the user account is locked.
     */
    public function isLocked(): bool
    {
        return !is_null($this->locked_at);
    }

    /**
     * Lock the user account.
     */
    public function lockAccount(): bool
    {
        return $this->forceFill([
            'locked_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Unlock the user account.
     */
    public function unlockAccount(): bool
    {
        return $this->forceFill([
            'locked_at' => null,
        ])->save();
    }
}
