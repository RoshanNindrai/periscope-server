<?php

namespace Periscope\AuthModule\Notifications\Channels\Providers;

interface SmsProviderInterface
{
    /**
     * Send an SMS message to the given phone number.
     *
     * @param  string  $phone  Phone number in E.164 format
     * @param  string  $message  Message content
     * @return void
     * @throws \Throwable
     */
    public function send(string $phone, string $message): void;
}
