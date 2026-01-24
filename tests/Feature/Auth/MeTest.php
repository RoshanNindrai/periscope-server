<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_401_without_token(): void
    {
        $this->getJson('/api/me')->assertStatus(401)->assertJsonPath('status', 'UNAUTHORIZED');
    }

    public function test_me_returns_200_with_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $r = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/me');
        $r->assertStatus(200)
            ->assertJsonPath('status', 'USER_RETRIEVED')
            ->assertJsonStructure(['user' => ['id', 'name', 'username']]);
    }
}
