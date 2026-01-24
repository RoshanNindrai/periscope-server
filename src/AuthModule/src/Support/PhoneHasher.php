<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Support;

use Periscope\AuthModule\Constants\AuthModuleConstants;
use Periscope\AuthModule\Contracts\PhoneHasherInterface;

final class PhoneHasher implements PhoneHasherInterface
{
    public function hash(string $phone): string
    {
        return hash(AuthModuleConstants::PHONE_HASH_ALGO, $phone);
    }
}
