<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * @param  array{name: string, username?: string, phone: string}  $attributes
     */
    public function create(array $attributes): User;

    public function findByPhoneHash(string $phoneHash): ?User;

    public function existsByPhoneHash(string $phoneHash): bool;

    public function existsByUsername(string $username): bool;

    /**
     * @param  list<string>  $select
     */
    public function findByUsernameExact(string $username, array $select = []): ?User;

    /**
     * @param  list<string>  $select
     */
    public function searchByUsernameOrName(string $term, int $perPage, array $select = []): LengthAwarePaginator;
}
