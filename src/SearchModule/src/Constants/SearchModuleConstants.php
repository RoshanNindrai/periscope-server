<?php

declare(strict_types=1);

namespace Periscope\SearchModule\Constants;

final class SearchModuleConstants
{
    private function __construct() {}

    // --- Config keys
    public const CONFIG_KEY = 'search-module';
    public const CONFIG_USER_MODEL = self::CONFIG_KEY . '.user_model';
    public const CONFIG_RESULTS_PER_PAGE = self::CONFIG_KEY . '.results_per_page';
    public const CONFIG_MIN_SEARCH_LENGTH = self::CONFIG_KEY . '.min_search_length';
    public const CONFIG_RATE_LIMIT = self::CONFIG_KEY . '.rate_limit';
    public const CONFIG_ROUTE_PREFIX = self::CONFIG_KEY . '.route_prefix';
    public const CONFIG_ROUTE_MIDDLEWARE = self::CONFIG_KEY . '.route_middleware';

    // --- Search rules
    public const SEARCH_TERM_MAX_LENGTH = 100;
    public const DEFAULT_MIN_SEARCH_LENGTH = 2;
    public const DEFAULT_RESULTS_PER_PAGE = 15;

    // --- User columns selected for search results
    /** @var list<string> */
    public const USER_SEARCH_SELECT = ['id', 'name', 'username', 'phone_verified_at'];
}
