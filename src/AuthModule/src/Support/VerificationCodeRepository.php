<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Support;

use Illuminate\Support\Facades\DB;
use Periscope\AuthModule\Contracts\VerificationCodeRepositoryInterface;

final class VerificationCodeRepository implements VerificationCodeRepositoryInterface
{
    public function __construct(
        private readonly string $table
    ) {}

    public function store(string $phoneHash, string $code): void
    {
        DB::table($this->table)->insert([
            'phone_hash' => $phoneHash,
            'code' => $code,
            'attempts' => 0,
            'created_at' => now(),
        ]);
    }

    public function find(string $phoneHash): ?object
    {
        return DB::table($this->table)->where('phone_hash', $phoneHash)->first();
    }

    public function delete(string $phoneHash): void
    {
        DB::table($this->table)->where('phone_hash', $phoneHash)->delete();
    }

    public function incrementAttempts(string $phoneHash): void
    {
        DB::table($this->table)->where('phone_hash', $phoneHash)->increment('attempts');
    }
}
