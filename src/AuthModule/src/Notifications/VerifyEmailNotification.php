<?php

namespace Periscope\AuthModule\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
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
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If you did not create an account, no further action is required.');
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl(object $notifiable): string
    {
        $frontendUrl = config('auth-module.frontend_url', env('FRONTEND_URL', env('APP_URL', 'http://localhost')));
        $routePrefix = config('auth-module.route_prefix', 'api');
        
        $expires = now()->addMinutes(config('auth.verification.expire', 60));
        $hash = sha1($notifiable->getEmailForVerification());
        
        // Create signed URL parameters
        $params = [
            'id' => $notifiable->getKey(),
            'hash' => $hash,
            'expires' => $expires->timestamp,
        ];
        
        // Generate signature using Laravel's signing
        $signature = hash_hmac('sha256', $notifiable->getKey() . '|' . $hash . '|' . $expires->timestamp, config('app.key'));
        $params['signature'] = $signature;
        
        // Return frontend URL that will call the API endpoint
        return $frontendUrl . '/verify-email?' . http_build_query($params);
    }
}
