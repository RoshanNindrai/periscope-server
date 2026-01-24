<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Support;

use Periscope\AuthModule\Constants\AuthModuleConstants;

final class PhoneMasker
{
    public static function mask(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '****';
        }
        $reveal = AuthModuleConstants::PHONE_MASK_REVEALED_DIGITS;
        if (strlen($phone) <= $reveal) {
            return str_repeat('*', strlen($phone));
        }
        return str_repeat('*', strlen($phone) - $reveal) . substr($phone, -$reveal);
    }
}
