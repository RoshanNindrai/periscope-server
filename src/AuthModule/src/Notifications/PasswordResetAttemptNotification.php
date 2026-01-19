<?php

namespace Periscope\AuthModule\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetAttemptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $lockToken
    ) {
        //
    }

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
        $lockUrl = $this->lockAccountUrl($notifiable);

        return (new MailMessage)
            ->subject('Password Reset Attempt - Action Required')
            ->line('We detected a password reset attempt on your account.')
            ->line('If you initiated this password reset, no further action is needed.')
            ->line('If you did NOT request this password reset, please click the button below immediately to secure your account.')
            ->action('Lock My Account - It Wasn\'t Me', $lockUrl)
            ->line('This link will expire in 15 minutes for security reasons.')
            ->line('If you don\'t recognize this activity, your account may have been compromised.');
    }

    /**
     * Get the lock account URL for the given notifiable.
     */
    protected function lockAccountUrl(object $notifiable): string
    {
        $frontendUrl = config('auth-module.frontend_url', env('FRONTEND_URL', env('APP_URL', 'http://localhost')));
        
        // Ensure consistent string types for signature
        $id = (string) $notifiable->getKey();
        $expiresTimestamp = (string) now()->addMinutes(15)->timestamp;
        
        // Get and decode APP_KEY (Laravel stores it as base64:...)
        $appKey = config('app.key');
        if (strpos($appKey, 'base64:') === 0) {
            $appKey = base64_decode(substr($appKey, 7));
        }
        
        $signature = hash_hmac('sha256', $id . '|' . $this->lockToken . '|' . $expiresTimestamp, $appKey);
        
        return $frontendUrl . '/lock-account?' . http_build_query([
            'id' => $id,
            'token' => $this->lockToken,
            'expires' => $expiresTimestamp,
            'signature' => $signature,
        ]);
    }
}
