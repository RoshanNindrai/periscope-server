<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Contracts;

interface PhoneHasherInterface
{
    public function hash(string $phone): string;
}
