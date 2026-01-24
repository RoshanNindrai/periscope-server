<?php

namespace Periscope\AuthModule\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VerifyPhoneNotification extends Notification implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $code
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
        return ['sms'];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return "Your verification code is: {$this->code}\n\nThis code will expire in 10 minutes. If you did not create an account, please ignore this message.";
    }

    /**
     * Get the phone number to send SMS to
     */
    public function routeNotificationForSms($notifiable)
    {
        return $notifiable->phone;
    }
}
