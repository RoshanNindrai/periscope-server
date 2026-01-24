<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Support;

use Periscope\AuthModule\Constants\AuthModuleConstants;
use Periscope\AuthModule\Contracts\VerificationCodeRepositoryInterface;

final class VerificationCodeRepositoryFactory
{
    public function forPhone(): VerificationCodeRepositoryInterface
    {
        return new VerificationCodeRepository(AuthModuleConstants::TABLE_PHONE_VERIFICATION_CODES);
    }

    public function forLogin(): VerificationCodeRepositoryInterface
    {
        return new VerificationCodeRepository(AuthModuleConstants::TABLE_LOGIN_VERIFICATION_CODES);
    }
}
