<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_login_send_otp_returns_200_when_user_exists(): void
    {
        User::factory()->create(['phone' => '+447911123459']);
        $r = $this->postJson('/api/login', ['phone' => '+447911123459']);
        $r->assertStatus(200)->assertJsonPath('status', 'LOGIN_CODE_SENT');
        $this->assertDatabaseHas('login_verification_codes', ['phone_hash' => hash('sha256', '+447911123459')]);
    }

    public function test_login_send_otp_returns_error_when_user_not_found(): void
    {
        $r = $this->postJson('/api/login', ['phone' => '+447911123460']);
        $r->assertStatus(404)->assertJsonPath('status', 'USER_NOT_FOUND');
    }

    public function test_login_send_otp_validation_error(): void
    {
        $r = $this->postJson('/api/login', []);
        $r->assertStatus(422)->assertJsonPath('status', 'VALIDATION_ERROR');
    }

    public function test_verify_login_returns_200_with_user_and_token(): void
    {
        $user = User::factory()->create(['phone' => '+447911123461']);
        $this->postJson('/api/login', ['phone' => '+447911123461']);
        $code = DB::table('login_verification_codes')->where('phone_hash', hash('sha256', '+447911123461'))->value('code');

        $r = $this->postJson('/api/verify-login', [
            'phone' => '+447911123461',
            'code' => $code,
        ]);
        $r->assertStatus(200)
            ->assertJsonPath('status', 'LOGGED_IN')
            ->assertJsonStructure(['user' => ['id', 'name', 'username'], 'token']);
    }

    public function test_verify_login_invalid_code_returns_422(): void
    {
        User::factory()->create(['phone' => '+447911123462']);
        $this->postJson('/api/login', ['phone' => '+447911123462']);

        $r = $this->postJson('/api/verify-login', [
            'phone' => '+447911123462',
            'code' => '000000',
        ]);
        $r->assertStatus(422)->assertJsonPath('status', 'INVALID_LOGIN_CODE');
    }

    public function test_verify_login_validation_error_for_wrong_code_length(): void
    {
        $r = $this->postJson('/api/verify-login', [
            'phone' => '+447911123463',
            'code' => '12345', // must be 6
        ]);
        $r->assertStatus(422)->assertJsonPath('status', 'VALIDATION_ERROR');
    }

    public function test_verify_login_bypass_with_magic_code_in_staging(): void
    {
        $this->app->instance('env', 'staging');
        putenv('AUTH_OTP_BYPASS_MAGIC=999999');

        User::factory()->create(['phone' => '+447911123464']);
        // No prior /login or sendOtp

        $r = $this->postJson('/api/verify-login', [
            'phone' => '+447911123464',
            'code' => '999999',
        ]);
        $r->assertStatus(200)
            ->assertJsonPath('status', 'LOGGED_IN')
            ->assertJsonStructure(['user' => ['id', 'name', 'username'], 'token']);
    }

    public function test_verify_login_magic_ignored_when_not_staging(): void
    {
        // env remains 'testing', so bypass is not used

        User::factory()->create(['phone' => '+447911123465']);
        $this->postJson('/api/login', ['phone' => '+447911123465']);

        $r = $this->postJson('/api/verify-login', [
            'phone' => '+447911123465',
            'code' => '999999', // magic, but env is not staging so it is ignored; real OTP is different
        ]);
        $r->assertStatus(422)->assertJsonPath('status', 'INVALID_LOGIN_CODE');
    }
}
