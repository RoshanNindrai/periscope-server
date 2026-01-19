<?php

namespace Periscope\AuthModule\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('auth-module.frontend_url', env('FRONTEND_URL', env('APP_URL', 'http://localhost')));

        return (new MailMessage)
            ->subject('Password Changed Successfully')
            ->line('Your password has been successfully changed.')
            ->line('If you did not make this change, please contact us immediately and reset your password.')
            ->action('Reset Password', $frontendUrl . '/forgot-password')
            ->line('This is an automated security notification to keep your account safe.');
    }
}
