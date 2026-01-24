<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Contracts;

interface VerificationCodeRepositoryInterface
{
    public function store(string $phone, string $code): void;

    public function find(string $phone): ?object;

    public function delete(string $phone): void;

    public function incrementAttempts(string $phone): void;
}
