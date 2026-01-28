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

    public function test_register_generates_username_when_not_provided(): void
    {
        $r = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'phone' => '+447911123459',
        ]);
        $r->assertStatus(201)
            ->assertJsonPath('status', 'REGISTERED')
            ->assertJsonStructure(['user' => ['id', 'name', 'username'], 'token']);

        $responseData = $r->json();
        $username = $responseData['user']['username'];

        // Verify username follows the pattern: john.doe.XXXX
        $this->assertMatchesRegularExpression('/^john\.doe\.\d{4}$/', $username);
        $this->assertDatabaseHas('users', ['username' => $username]);
    }

    public function test_register_uses_provided_username_when_given(): void
    {
        $r = $this->postJson('/api/register', [
            'name' => 'Test User',
            'username' => 'customusername',
            'phone' => '+447911123460',
        ]);
        $r->assertStatus(201)
            ->assertJsonPath('status', 'REGISTERED')
            ->assertJsonPath('user.username', 'customusername')
            ->assertJsonStructure(['user' => ['id', 'name', 'username'], 'token']);
        $this->assertDatabaseHas('users', ['username' => 'customusername']);
    }

    public function test_register_handles_username_collision(): void
    {
        // Create a user with a username that might collide
        $this->postJson('/api/register', [
            'name' => 'John Doe',
            'username' => 'john.doe.1234',
            'phone' => '+447911123461',
        ])->assertStatus(201);

        // Register another user with same name (should generate different username)
        $r = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'phone' => '+447911123462',
        ]);
        $r->assertStatus(201)
            ->assertJsonPath('status', 'REGISTERED')
            ->assertJsonStructure(['user' => ['id', 'name', 'username'], 'token']);

        $responseData = $r->json();
        $username = $responseData['user']['username'];

        // Should be john.doe.XXXX but not john.doe.1234
        $this->assertMatchesRegularExpression('/^john\.doe\.\d{4}$/', $username);
        $this->assertNotEquals('john.doe.1234', $username);
        $this->assertDatabaseHas('users', ['username' => $username]);
    }
}
