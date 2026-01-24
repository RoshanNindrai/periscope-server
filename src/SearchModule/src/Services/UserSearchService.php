<?php

declare(strict_types=1);

namespace Periscope\SearchModule\Services;

use App\Contracts\UserRepositoryInterface;
use Periscope\SearchModule\Constants\SearchModuleConstants;
use Periscope\SearchModule\Contracts\UserSearchServiceInterface;
use Periscope\SearchModule\DTO\UserSearchResult;
use Periscope\SearchModule\Enums\SearchResponseState;
use Throwable;

final class UserSearchService implements UserSearchServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly int $perPage,
    ) {}

    /**
     * @throws Throwable
     */
    public function search(string $q): UserSearchResult
    {
        $term = trim($q);
        $normalized = strtolower($term);
        $select = SearchModuleConstants::USER_SEARCH_SELECT;

        $exact = $this->userRepository->findByUsernameExact($normalized, $select);

        if ($exact !== null) {
            return new UserSearchResult(
                SearchResponseState::USERS_FOUND,
                SearchResponseState::USERS_FOUND->message(),
                [$exact],
                $this->meta(1, 1, 1),
            );
        }

        $paginator = $this->userRepository->searchByUsernameOrName($term, $this->perPage, $select);

        if ($paginator->isEmpty()) {
            return new UserSearchResult(
                SearchResponseState::NO_RESULTS_FOUND,
                SearchResponseState::NO_RESULTS_FOUND->message(),
                [],
                $this->meta(1, 0, 1),
            );
        }

        return new UserSearchResult(
            SearchResponseState::USERS_FOUND,
            SearchResponseState::USERS_FOUND->message(),
            $paginator->items(),
            $this->meta(
                $paginator->currentPage(),
                $paginator->total(),
                $paginator->lastPage(),
            ),
        );
    }

    /**
     * @return array{current_page: int, per_page: int, total: int, last_page: int}
     */
    private function meta(int $currentPage, int $total, int $lastPage): array
    {
        return [
            'current_page' => $currentPage,
            'per_page' => $this->perPage,
            'total' => $total,
            'last_page' => $lastPage,
        ];
    }
}
