<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\StagingMagicBypassInterface;

final class StagingMagicBypass implements StagingMagicBypassInterface
{
    public function allows(string $feature, string $providedValue): bool
    {
        if (!app()->environment('staging')) {
            return false;
        }

        $envKey = config('staging-bypass.features.' . $feature);
        if ($envKey === null || $envKey === '') {
            return false;
        }

        $magic = env($envKey);
        return is_string($magic) && $magic !== '' && hash_equals($magic, $providedValue);
    }
}
