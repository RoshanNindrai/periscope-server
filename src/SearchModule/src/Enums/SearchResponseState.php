<?php

namespace Periscope\SearchModule\Enums;

enum SearchResponseState: string
{
    case USERS_FOUND = 'USERS_FOUND';
    case NO_RESULTS_FOUND = 'NO_RESULTS_FOUND';

    public function message(): string
    {
        return match ($this) {
            self::USERS_FOUND => 'Users found matching your search.',
            self::NO_RESULTS_FOUND => 'No users found matching your search.',
        };
    }
}
