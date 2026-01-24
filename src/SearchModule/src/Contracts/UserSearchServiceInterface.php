<?php

declare(strict_types=1);

namespace Periscope\SearchModule\Contracts;

use Periscope\SearchModule\DTO\UserSearchResult;

interface UserSearchServiceInterface
{
    /**
     * Search users by username or name prefix.
     * Caller must validate search term (min/max length) before calling.
     *
     * @throws \Throwable
     */
    public function search(string $q): UserSearchResult;
}
