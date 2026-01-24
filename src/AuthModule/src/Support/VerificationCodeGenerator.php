<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Support;

use Periscope\AuthModule\Constants\AuthModuleConstants;
use Periscope\AuthModule\Contracts\VerificationCodeGeneratorInterface;

final class VerificationCodeGenerator implements VerificationCodeGeneratorInterface
{
    public function generate(): string
    {
        return str_pad(
            (string) random_int(0, 10 ** AuthModuleConstants::CODE_LENGTH - 1),
            AuthModuleConstants::CODE_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }
}
