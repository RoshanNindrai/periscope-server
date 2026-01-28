<?php

namespace Tests\Unit;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->app->make(UserRepositoryInterface::class);
    }

    public function test_create_persists_user(): void
    {
        $user = $this->repo->create([
            'name' => 'Test',
            'username' => 'testuser',
            'phone' => '+15555551234',
        ]);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Test', $user->name);
        $this->assertSame('testuser', $user->username);
        $this->assertDatabaseHas('users', ['username' => 'testuser']);
    }

    public function test_find_by_phone_hash_returns_user(): void
    {
        $user = User::factory()->create(['phone' => '+15555551111']);
        $hash = hash('sha256', '+15555551111');
        $found = $this->repo->findByPhoneHash($hash);
        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function test_find_by_phone_hash_returns_null_when_missing(): void
    {
        $this->assertNull($this->repo->findByPhoneHash(hash('sha256', '+15555559999')));
    }

    public function test_exists_by_phone_hash(): void
    {
        User::factory()->create(['phone' => '+15555552222']);
        $hash = hash('sha256', '+15555552222');
        $this->assertTrue($this->repo->existsByPhoneHash($hash));
        $this->assertFalse($this->repo->existsByPhoneHash(hash('sha256', '+15555553333')));
    }

    public function test_exists_by_username(): void
    {
        User::factory()->create(['username' => 'existinguser']);
        $this->assertTrue($this->repo->existsByUsername('existinguser'));
        $this->assertFalse($this->repo->existsByUsername('nonexistentuser'));
    }

    public function test_find_by_username_exact(): void
    {
        User::factory()->create(['username' => 'alice']);
        $this->assertNotNull($this->repo->findByUsernameExact('alice'));
        $this->assertNull($this->repo->findByUsernameExact('bob'));
    }

    public function test_search_by_username_or_name(): void
    {
        User::factory()->create(['username' => 'aliceb', 'name' => 'Alice B']);
        User::factory()->create(['username' => 'alicec', 'name' => 'Alice C']);
        User::factory()->create(['username' => 'bob', 'name' => 'Bob']);
        $paginator = $this->repo->searchByUsernameOrName('alice', 10);
        $this->assertGreaterThanOrEqual(2, $paginator->total());
        $usernames = collect($paginator->items())->pluck('username')->all();
        $this->assertContains('aliceb', $usernames);
        $this->assertContains('alicec', $usernames);
    }
}
