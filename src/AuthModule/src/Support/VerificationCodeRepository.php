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

    public function store(string $phone, string $code): void
    {
        DB::table($this->table)->insert([
            'phone' => $phone,
            'code' => $code,
            'attempts' => 0,
            'created_at' => now(),
        ]);
    }

    public function find(string $phone): ?object
    {
        return DB::table($this->table)->where('phone', $phone)->first();
    }

    public function delete(string $phone): void
    {
        DB::table($this->table)->where('phone', $phone)->delete();
    }

    public function incrementAttempts(string $phone): void
    {
        DB::table($this->table)->where('phone', $phone)->increment('attempts');
    }
}
