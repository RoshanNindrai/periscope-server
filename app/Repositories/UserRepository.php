<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly string $modelClass
    ) {}

    public function create(array $attributes): User
    {
        $user = $this->modelClass::create($attributes);
        assert($user instanceof User);
        return $user;
    }

    public function findByPhoneHash(string $phoneHash): ?User
    {
        $user = $this->modelClass::where('phone_hash', $phoneHash)->first();
        return $user instanceof User ? $user : null;
    }

    public function existsByPhoneHash(string $phoneHash): bool
    {
        return $this->modelClass::where('phone_hash', $phoneHash)->exists();
    }

    public function existsByUsername(string $username): bool
    {
        return $this->modelClass::where('username', $username)->exists();
    }

    public function findByUsernameExact(string $username, array $select = []): ?User
    {
        $query = $this->modelClass::where('username', $username);
        if ($select !== []) {
            $query->select($select);
        }
        $user = $query->first();
        return $user instanceof User ? $user : null;
    }

    public function searchByUsernameOrName(string $term, int $perPage, array $select = []): LengthAwarePaginator
    {
        $normalized = strtolower(trim($term));

        $query = $this->modelClass::query()
            ->where(fn ($q) => $q
                ->where('username', 'like', $normalized . '%')
                ->orWhere('name', 'like', $normalized . '%'))
            ->orderByRaw(
                'CASE WHEN username LIKE ? THEN 1 WHEN name LIKE ? THEN 2 ELSE 3 END',
                [$normalized . '%', $normalized . '%']
            )
            ->orderBy('username');

        if ($select !== []) {
            $query->select($select);
        }

        return $query->paginate($perPage);
    }
}
