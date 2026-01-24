<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:prune-failed', ['--hours' => 48])->daily();

Schedule::call(function (): void {
    DB::table('phone_verification_codes')->where('created_at', '<', now()->subHour())->delete();
    DB::table('login_verification_codes')->where('created_at', '<', now()->subHour())->delete();
})->hourly();
