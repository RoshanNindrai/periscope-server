<?php

namespace Tests\Unit;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Periscope\SearchModule\Enums\SearchResponseState;
use Periscope\SearchModule\Services\UserSearchService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class UserSearchServiceTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepo;

    private UserSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->service = new UserSearchService($this->userRepo, 15);
    }

    public function test_search_returns_users_found_when_exact_username_match(): void
    {
        $user = User::factory()->make(['id' => 1, 'name' => 'Alice', 'username' => 'alice']);
        $this->userRepo->method('findByUsernameExact')->with('alice', $this->anything())->willReturn($user);
        $this->userRepo->expects($this->never())->method('searchByUsernameOrName');

        $result = $this->service->search('alice');

        $this->assertSame(SearchResponseState::USERS_FOUND, $result->status);
        $this->assertCount(1, $result->data);
        $this->assertSame($user, $result->data[0]);
        $this->assertSame(1, $result->meta['current_page']);
        $this->assertSame(15, $result->meta['per_page']);
        $this->assertSame(1, $result->meta['total']);
        $this->assertSame(1, $result->meta['last_page']);
    }

    public function test_search_returns_no_results_when_search_empty(): void
    {
        $this->userRepo->method('findByUsernameExact')->willReturn(null);
        $paginator = new LengthAwarePaginator([], 0, 15, 1);
        $this->userRepo->method('searchByUsernameOrName')->willReturn($paginator);

        $result = $this->service->search('xyznonexistent');

        $this->assertSame(SearchResponseState::NO_RESULTS_FOUND, $result->status);
        $this->assertSame([], $result->data);
        $this->assertSame(0, $result->meta['total']);
    }

    public function test_search_returns_users_found_when_search_matches(): void
    {
        $this->userRepo->method('findByUsernameExact')->willReturn(null);
        $bob = User::factory()->make(['id' => 2, 'name' => 'Bob', 'username' => 'bobby']);
        $paginator = new LengthAwarePaginator([$bob], 1, 15, 1);
        $this->userRepo->method('searchByUsernameOrName')->with('bob', 15, $this->anything())->willReturn($paginator);

        $result = $this->service->search('bob');

        $this->assertSame(SearchResponseState::USERS_FOUND, $result->status);
        $this->assertCount(1, $result->data);
        $this->assertSame(1, $result->meta['current_page']);
        $this->assertSame(1, $result->meta['total']);
        $this->assertSame(1, $result->meta['last_page']);
    }

    public function test_search_trims_and_lowercases_for_exact_lookup(): void
    {
        $user = User::factory()->make(['id' => 3, 'name' => 'Charlie', 'username' => 'charlie']);
        $this->userRepo->method('findByUsernameExact')->with('charlie', $this->anything())->willReturn($user);
        $this->userRepo->expects($this->never())->method('searchByUsernameOrName');

        $result = $this->service->search('  CHARLIE  ');

        $this->assertSame(SearchResponseState::USERS_FOUND, $result->status);
        $this->assertCount(1, $result->data);
    }
}
