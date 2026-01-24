<?php

namespace Periscope\AuthModule\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Periscope\AuthModule\Notifications\Channels\Providers\SnsSmsProvider;

class SmsChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        // Get phone number from notifiable
        $phone = $notifiable->routeNotificationFor('sms', $notification);
        
        if (!$phone) {
            Log::warning('No phone number found for SMS notification', [
                'notifiable_id' => $notifiable->id ?? null,
                'notification' => get_class($notification),
            ]);
            return;
        }

        // Get message from notification
        $message = $notification->toSms($notifiable);

        // Send via AWS SNS. Rethrow with generic message so OTP never appears in failed_jobs.exception
        try {
            $provider = new SnsSmsProvider();
            $provider->send($phone, $message);
        } catch (\Throwable $e) {
            Log::error('Failed to send SMS notification', [
                'phone' => $this->maskPhone($phone),
                'error' => $e->getMessage(),
                'notification' => get_class($notification),
            ]);

            throw new \RuntimeException('SMS delivery failed.', 0, $e);
        }
    }

    /**
     * Mask phone number for logging (show last 4 digits only)
     *
     * @param  string  $phone
     * @return string
     */
    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return '****';
        }
        
        return str_repeat('*', strlen($phone) - 4) . substr($phone, -4);
    }
}
