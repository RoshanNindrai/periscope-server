<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_register_returns_201_with_user_and_token(): void
    {
        $r = $this->postJson('/api/register', [
            'name' => 'Test User',
            'username' => 'testuser99',
            'phone' => '+447911123456',
        ]);
        $r->assertStatus(201)
            ->assertJsonPath('status', 'REGISTERED')
            ->assertJsonStructure(['user' => ['id', 'name', 'username'], 'token']);
        $this->assertDatabaseHas('users', ['username' => 'testuser99']);
    }

    public function test_register_validation_error_for_duplicate_phone(): void
    {
        $this->postJson('/api/register', [
            'name' => 'First',
            'username' => 'firstuser',
            'phone' => '+447911123457',
        ])->assertStatus(201);

        $r = $this->postJson('/api/register', [
            'name' => 'Second',
            'username' => 'seconduser',
            'phone' => '+447911123457',
        ]);
        $r->assertStatus(422)
            ->assertJsonPath('status', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors']);
    }

    public function test_register_validation_error_for_missing_fields(): void
    {
        $r = $this->postJson('/api/register', ['name' => 'Only']);
        $r->assertStatus(422)->assertJsonPath('status', 'VALIDATION_ERROR');
    }

    public function test_register_validation_error_for_invalid_username(): void
    {
        $r = $this->postJson('/api/register', [
            'name' => 'Test',
            'username' => 'ab', // min 3
            'phone' => '+447911123458',
        ]);
        $r->assertStatus(422)->assertJsonPath('status', 'VALIDATION_ERROR');
    }
}
