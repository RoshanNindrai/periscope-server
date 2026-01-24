<?php

declare(strict_types=1);

namespace App\GraphQL\Exceptions;

use GraphQL\Error\ProvidesExtensions;

final class SearchFailedException extends \Exception implements ProvidesExtensions
{
    public const CODE = 'SEARCH_FAILED';

    public function getExtensions(): array
    {
        return ['code' => self::CODE];
    }
}
