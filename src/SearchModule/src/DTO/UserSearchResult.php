<?php

declare(strict_types=1);

namespace Periscope\SearchModule\DTO;

use Periscope\SearchModule\Enums\SearchResponseState;

final readonly class UserSearchResult
{
    /**
     * @param  array<int, object>  $data
     * @param  array{current_page: int, per_page: int, total: int, last_page: int}  $meta
     */
    public function __construct(
        public SearchResponseState $status,
        public string $message,
        public array $data,
        public array $meta,
    ) {}

    public function toGraphQLMeta(): array
    {
        return [
            'currentPage' => $this->meta['current_page'],
            'perPage' => $this->meta['per_page'],
            'total' => $this->meta['total'],
            'lastPage' => $this->meta['last_page'],
        ];
    }
}
