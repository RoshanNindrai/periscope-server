<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:prune-failed', ['--hours' => 48])->daily();

Schedule::call(function (): void {
    DB::table('phone_verification_codes')->where('created_at', '<', now()->subHour())->delete();
    DB::table('login_verification_codes')->where('created_at', '<', now()->subHour())->delete();
})->hourly();
