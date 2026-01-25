<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\StagingBypassFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerifyPhoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_verify_phone_returns_200_phone_verified(): void
    {
        $user = User::factory()->create(['phone' => '+447911123464']);
        DB::table('phone_verification_codes')->insert([
            'phone_hash' => hash('sha256', '+447911123464'),
            'code' => '123456',
            'attempts' => 0,
            'created_at' => now(),
        ]);

        $r = $this->postJson('/api/verify-phone', [
            'phone' => '+447911123464',
            'code' => '123456',
        ]);
        $r->assertStatus(200)->assertJsonPath('status', 'PHONE_VERIFIED');
        $user->refresh();
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_verify_phone_already_verified_returns_200_with_state(): void
    {
        $user = User::factory()->create([
            'phone' => '+447911123465',
            'phone_verified_at' => now(),
        ]);

        $r = $this->postJson('/api/verify-phone', [
            'phone' => '+447911123465',
            'code' => '123456',
        ]);
        $r->assertStatus(200)->assertJsonPath('status', 'PHONE_ALREADY_VERIFIED');
    }

    public function test_verify_phone_invalid_code_returns_422(): void
    {
        User::factory()->create(['phone' => '+447911123466']);
        DB::table('phone_verification_codes')->insert([
            'phone_hash' => hash('sha256', '+447911123466'),
            'code' => '123456',
            'attempts' => 0,
            'created_at' => now(),
        ]);

        $r = $this->postJson('/api/verify-phone', [
            'phone' => '+447911123466',
            'code' => '999999',
        ]);
        $r->assertStatus(422)->assertJsonPath('status', 'INVALID_VERIFICATION_CODE');
    }

    public function test_verify_phone_validation_error(): void
    {
        $r = $this->postJson('/api/verify-phone', ['phone' => '+447911123467']);
        $r->assertStatus(422)->assertJsonPath('status', 'VALIDATION_ERROR');
    }

    public function test_verify_phone_bypass_with_magic_code_in_staging(): void
    {
        $this->app->instance('env', 'staging');
        Config::set('staging-bypass.features.' . StagingBypassFeature::PHONE_VERIFICATION, '888888');

        $user = User::factory()->create(['phone' => '+447911123468']);
        // No phone_verification_codes record; no prior register/resend

        $r = $this->postJson('/api/verify-phone', [
            'phone' => '+447911123468',
            'code' => '888888',
        ]);
        $r->assertStatus(200)->assertJsonPath('status', 'PHONE_VERIFIED');
        $user->refresh();
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_verify_phone_magic_ignored_when_not_staging(): void
    {
        // env remains 'testing', so bypass is not used

        User::factory()->create(['phone' => '+447911123469']);
        DB::table('phone_verification_codes')->insert([
            'phone_hash' => hash('sha256', '+447911123469'),
            'code' => '123456',
            'attempts' => 0,
            'created_at' => now(),
        ]);

        $r = $this->postJson('/api/verify-phone', [
            'phone' => '+447911123469',
            'code' => '888888', // magic, but env is not staging so it is ignored
        ]);
        $r->assertStatus(422)->assertJsonPath('status', 'INVALID_VERIFICATION_CODE');
    }
}
