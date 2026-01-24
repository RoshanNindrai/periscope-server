<?php

namespace Tests\Feature\GraphQL;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchUsersGraphQLTest extends TestCase
{
    use RefreshDatabase;

    private string $searchUsersQuery = <<<'GQL'
query SearchUsers($q: String!) {
  searchUsers(q: $q) {
    status
    message
    data { id name username phoneVerifiedAt }
    meta { currentPage perPage total lastPage }
  }
}
GQL;

    public function test_search_users_returns_unauthorized_without_auth(): void
    {
        $r = $this->postJson('/graphql', [
            'query' => $this->searchUsersQuery,
            'variables' => ['q' => 'al'],
        ]);
        $r->assertStatus(200);
        $body = $r->json();
        $this->assertArrayHasKey('errors', $body);
        $this->assertNotEmpty($body['errors']);
        $first = $body['errors'][0];
        $this->assertArrayHasKey('extensions', $first);
        $this->assertSame('UNAUTHORIZED', $first['extensions']['code'] ?? null);
    }

    public function test_search_users_returns_validation_error_for_short_query(): void
    {
        $user = User::factory()->create();
        $r = $this->actingAs($user, 'sanctum')->postJson('/graphql', [
            'query' => $this->searchUsersQuery,
            'variables' => ['q' => 'a'],
        ]);
        $r->assertStatus(200);
        $body = $r->json();
        $this->assertArrayHasKey('errors', $body);
        $this->assertNotEmpty($body['errors']);
    }

    public function test_search_users_returns_data_when_authenticated(): void
    {
        User::factory()->create(['username' => 'alice', 'name' => 'Alice']);
        $user = User::factory()->create();

        $r = $this->actingAs($user, 'sanctum')->postJson('/graphql', [
            'query' => $this->searchUsersQuery,
            'variables' => ['q' => 'alice'],
        ]);
        $r->assertStatus(200);
        $body = $r->json();
        $this->assertArrayNotHasKey('errors', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('searchUsers', $body['data']);
        $search = $body['data']['searchUsers'];
        $this->assertSame('USERS_FOUND', $search['status']);
        $this->assertIsArray($search['data']);
        $this->assertGreaterThanOrEqual(1, count($search['data']));
        $this->assertArrayHasKey('meta', $search);
        $this->assertArrayHasKey('currentPage', $search['meta']);
        $this->assertArrayHasKey('perPage', $search['meta']);
        $this->assertArrayHasKey('total', $search['meta']);
        $this->assertArrayHasKey('lastPage', $search['meta']);
    }

    public function test_search_users_returns_no_results_found(): void
    {
        $user = User::factory()->create();

        $r = $this->actingAs($user, 'sanctum')->postJson('/graphql', [
            'query' => $this->searchUsersQuery,
            'variables' => ['q' => 'xyznonexistent'],
        ]);
        $r->assertStatus(200);
        $body = $r->json();
        $this->assertArrayNotHasKey('errors', $body);
        $this->assertSame('NO_RESULTS_FOUND', $body['data']['searchUsers']['status']);
        $this->assertSame([], $body['data']['searchUsers']['data']);
    }
}
