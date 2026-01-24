<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Contracts;

interface VerificationCodeGeneratorInterface
{
    public function generate(): string;
}
