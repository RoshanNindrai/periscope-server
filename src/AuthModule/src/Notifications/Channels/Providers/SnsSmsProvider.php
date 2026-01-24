<?php

namespace Periscope\AuthModule\Notifications\Channels\Providers;

use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class SnsSmsProvider implements SmsProviderInterface
{
    /**
     * Singleton SNS client instance
     */
    protected static ?SnsClient $snsClient = null;

    /**
     * Send an SMS message via AWS SNS.
     *
     * @param  string  $phone  Phone number in E.164 format
     * @param  string  $message  Message content
     * @return void
     * @throws \Throwable
     */
    public function send(string $phone, string $message): void
    {
        // Local development mode: Log SMS instead of sending (never log message body / OTP)
        if (config('app.env') === 'local' && !config('auth-module.aws.sns.key')) {
            Log::info('📱 LOCAL DEV: SMS would be sent via AWS SNS', [
                'phone' => $this->maskPhone($phone),
                'message_length' => strlen($message),
                'provider' => 'sns',
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

            Log::info('SMS sent successfully via AWS SNS', [
                'phone' => $this->maskPhone($phone),
                'message_id' => $result['MessageId'] ?? null,
                'provider' => 'sns',
            ]);
        } catch (AwsException $e) {
            Log::error('Failed to send SMS via AWS SNS', [
                'phone' => $this->maskPhone($phone),
                'error' => $e->getMessage(),
                'aws_error_code' => $e->getAwsErrorCode(),
                'provider' => 'sns',
            ]);
            
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unexpected error sending SMS via AWS SNS', [
                'phone' => $this->maskPhone($phone),
                'error' => $e->getMessage(),
                'provider' => 'sns',
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
