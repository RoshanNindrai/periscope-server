<?php

namespace Periscope\AuthModule\Notifications\Channels;

use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * Singleton SNS client instance
     */
    protected static ?SnsClient $snsClient = null;

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

        // Local development mode: Log SMS instead of sending
        if (config('app.env') === 'local' && !config('auth-module.aws.sns.key')) {
            Log::info('📱 LOCAL DEV: SMS would be sent', [
                'phone' => $this->maskPhone($phone),
                'message' => $message, // Message includes OTP, only log in local dev
                'notification' => get_class($notification),
            ]);
            return;
        }

        // Send SMS via AWS SNS
        try {
            $sns = $this->getSnsClient();
            
            $result = $sns->publish([
                'Message' => $message,
                'PhoneNumber' => $phone,
            ]);

            Log::info('SMS sent successfully', [
                'phone' => $this->maskPhone($phone),
                'message_id' => $result['MessageId'] ?? null,
                'notification' => get_class($notification),
            ]);
        } catch (AwsException $e) {
            Log::error('Failed to send SMS via AWS SNS', [
                'phone' => $this->maskPhone($phone),
                'error' => $e->getMessage(),
                'aws_error_code' => $e->getAwsErrorCode(),
                'notification' => get_class($notification),
            ]);
            
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unexpected error sending SMS', [
                'phone' => $this->maskPhone($phone),
                'error' => $e->getMessage(),
                'notification' => get_class($notification),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get configured SNS client (singleton)
     *
     * @return \Aws\Sns\SnsClient
     */
    protected function getSnsClient(): SnsClient
    {
        if (self::$snsClient === null) {
            self::$snsClient = new SnsClient([
                'region' => config('auth-module.aws.sns.region', env('AWS_SNS_REGION', 'us-east-1')),
                'version' => 'latest',
                'credentials' => [
                    'key' => config('auth-module.aws.sns.key', env('AWS_ACCESS_KEY_ID')),
                    'secret' => config('auth-module.aws.sns.secret', env('AWS_SECRET_ACCESS_KEY')),
                ],
            ]);
        }
        
        return self::$snsClient;
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
