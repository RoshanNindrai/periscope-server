<?php

namespace Periscope\SearchModule\Enums;

enum SearchErrorCode: string
{
    case VALIDATION_ERROR = 'validation_error';
    case SEARCH_TERM_TOO_SHORT = 'search_term_too_short';
    case SEARCH_FAILED = 'search_failed';

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
