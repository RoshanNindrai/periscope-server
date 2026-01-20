<?php

namespace Periscope\SearchModule\Enums;

enum SearchResponseState: string
{
    case USERS_FOUND = 'users_found';
    case NO_RESULTS_FOUND = 'no_results_found';
    case SEARCH_SUCCESS = 'search_success';

    public function message(): string
    {
        return match($this) {
            self::USERS_FOUND => 'Users found matching your search.',
            self::NO_RESULTS_FOUND => 'No users found matching your search.',
            self::SEARCH_SUCCESS => 'Search completed successfully.',
        };
    }
}
