<?php

namespace Periscope\SearchModule\Enums;

enum SearchErrorCode: string
{
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case SEARCH_TERM_TOO_SHORT = 'SEARCH_TERM_TOO_SHORT';
    case SEARCH_FAILED = 'SEARCH_FAILED';

    public function message(): string
    {
        return match($this) {
            self::VALIDATION_ERROR => 'Validation failed for the provided search parameters.',
            self::SEARCH_TERM_TOO_SHORT => 'Search term must be at least 2 characters long.',
            self::SEARCH_FAILED => 'An error occurred while searching for users.',
        };
    }

    public function statusCode(): int
    {
        return match($this) {
            self::VALIDATION_ERROR => 422,
            self::SEARCH_TERM_TOO_SHORT => 422,
            self::SEARCH_FAILED => 500,
        };
    }
}
