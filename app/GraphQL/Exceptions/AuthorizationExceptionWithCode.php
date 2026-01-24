<?php

declare(strict_types=1);

namespace App\GraphQL\Exceptions;

use GraphQL\Error\ProvidesExtensions;
use Illuminate\Auth\Access\AuthorizationException as LaravelAuthorizationException;
use Nuwave\Lighthouse\Exceptions\AuthorizationException as LighthouseAuthorizationException;

final class AuthorizationExceptionWithCode extends LighthouseAuthorizationException implements ProvidesExtensions
{
    public const CODE = 'FORBIDDEN';

    public function getExtensions(): array
    {
        return ['code' => self::CODE];
    }

    public static function fromLaravel(LaravelAuthorizationException $e): self
    {
        return new self($e->getMessage(), $e->getCode());
    }
}
