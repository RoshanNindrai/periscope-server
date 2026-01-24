<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResendVerificationSmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_resend_returns_401_without_token(): void
    {
        $this->postJson('/api/resend-verification-sms')->assertStatus(401);
    }

    public function test_resend_returns_200_verification_sms_sent(): void
    {
        $user = User::factory()->create(['phone' => '+15555550900']);

        $r = $this->actingAs($user, 'sanctum')->postJson('/api/resend-verification-sms');
        $r->assertStatus(200)->assertJsonPath('status', 'VERIFICATION_SMS_SENT');
        $this->assertDatabaseHas('phone_verification_codes', ['phone' => '+15555550900']);
    }

    public function test_resend_returns_phone_already_verified_when_done(): void
    {
        $user = User::factory()->create([
            'phone' => '+15555550950',
            'phone_verified_at' => now(),
        ]);

        $r = $this->actingAs($user, 'sanctum')->postJson('/api/resend-verification-sms');
        $r->assertStatus(200)->assertJsonPath('status', 'PHONE_ALREADY_VERIFIED');
    }
}
