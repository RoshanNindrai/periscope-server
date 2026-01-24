<?php

declare(strict_types=1);

namespace Periscope\SearchModule\Providers;

use Illuminate\Support\ServiceProvider;
use Periscope\SearchModule\Constants\SearchModuleConstants;
use Periscope\SearchModule\Contracts\UserSearchServiceInterface;
use Periscope\SearchModule\Services\UserSearchService;

class SearchModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/search-module.php',
            SearchModuleConstants::CONFIG_KEY
        );

        $this->app->bind(UserSearchServiceInterface::class, UserSearchService::class);

        $this->app->when(UserSearchService::class)
            ->needs('$perPage')
            ->give(fn () => (int) config(SearchModuleConstants::CONFIG_RESULTS_PER_PAGE, SearchModuleConstants::DEFAULT_RESULTS_PER_PAGE));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/search-module.php' => config_path('search-module.php'),
        ], 'search-module-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'search-module-migrations');
    }
}
