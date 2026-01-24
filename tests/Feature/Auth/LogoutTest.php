<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_returns_401_without_token(): void
    {
        $this->postJson('/api/logout')->assertStatus(401);
    }

    public function test_logout_returns_200_and_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $pa = PersonalAccessToken::findToken($token);
        $this->assertNotNull($pa, 'Token must exist before logout');
        $id = $pa->id;

        $r = $this->withHeader('Authorization', 'Bearer ' . $token)->postJson('/api/logout');
        $r->assertStatus(200)->assertJsonPath('status', 'LOGGED_OUT');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $id]);
        $this->assertNull(PersonalAccessToken::findToken($token), 'Revoked token must not be findable');
    }
}
