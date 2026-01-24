<?php

declare(strict_types=1);

namespace App\GraphQL\Exceptions;

use GraphQL\Error\ProvidesExtensions;
use Illuminate\Auth\AuthenticationException as LaravelAuthenticationException;
use Nuwave\Lighthouse\Exceptions\AuthenticationException as LighthouseAuthenticationException;

final class AuthenticationExceptionWithCode extends LighthouseAuthenticationException implements ProvidesExtensions
{
    public const CODE = 'UNAUTHORIZED';

    public function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), ['code' => self::CODE]);
    }

    public static function fromLaravel(LaravelAuthenticationException $e): self
    {
        return new self($e->getMessage(), $e->guards());
    }
}
