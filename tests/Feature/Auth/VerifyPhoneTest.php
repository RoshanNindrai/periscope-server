<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
