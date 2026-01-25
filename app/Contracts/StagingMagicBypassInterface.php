<?php

declare(strict_types=1);

namespace App\Contracts;

interface StagingMagicBypassInterface
{
    /**
     * Whether the given value is the configured magic bypass for the feature in staging.
     * Returns false when not in staging, feature is not configured, or value does not match.
     */
    public function allows(string $feature, string $providedValue): bool;
}
