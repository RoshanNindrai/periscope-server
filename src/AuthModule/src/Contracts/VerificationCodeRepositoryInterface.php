<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Contracts;

interface VerificationCodeRepositoryInterface
{
    public function store(string $phoneHash, string $code): void;

    public function find(string $phoneHash): ?object;

    public function delete(string $phoneHash): void;

    public function incrementAttempts(string $phoneHash): void;
}
