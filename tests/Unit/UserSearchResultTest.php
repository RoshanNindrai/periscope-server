<?php

namespace Tests\Unit;

use Periscope\SearchModule\DTO\UserSearchResult;
use Periscope\SearchModule\Enums\SearchResponseState;
use PHPUnit\Framework\TestCase;

class UserSearchResultTest extends TestCase
{
    public function test_to_graphql_meta_maps_snake_to_camel(): void
    {
        $result = new UserSearchResult(
            SearchResponseState::USERS_FOUND,
            'Found.',
            [],
            ['current_page' => 2, 'per_page' => 15, 'total' => 30, 'last_page' => 2],
        );

        $meta = $result->toGraphQLMeta();

        $this->assertSame(2, $meta['currentPage']);
        $this->assertSame(15, $meta['perPage']);
        $this->assertSame(30, $meta['total']);
        $this->assertSame(2, $meta['lastPage']);
    }
}
