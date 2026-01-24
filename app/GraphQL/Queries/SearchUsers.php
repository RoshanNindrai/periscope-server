<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Exceptions\SearchFailedException;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Log;
use Periscope\SearchModule\Contracts\UserSearchServiceInterface;
use Periscope\SearchModule\Enums\SearchErrorCode;
use Throwable;

class SearchUsers
{
    public function __construct(
        private readonly UserSearchServiceInterface $userSearchService,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array{status: string, message: string, data: array, meta: array{currentPage: int, perPage: int, total: int, lastPage: int}}
     *
     * @throws Error
     */
    public function __invoke(mixed $root, array $args): array
    {
        try {
            $result = $this->userSearchService->search($args['q']);
        } catch (Throwable $e) {
            Log::error('User search failed', [
                'search_term_length' => strlen($args['q'] ?? ''),
                'error' => $e->getMessage(),
            ]);
            throw new Error(
                SearchErrorCode::SEARCH_FAILED->message(),
                null,
                null,
                null,
                null,
                new SearchFailedException(),
            );
        }

        return [
            'status' => $result->status->value,
            'message' => $result->message,
            'data' => $result->data,
            'meta' => $result->toGraphQLMeta(),
        ];
    }
}
