<?php

declare(strict_types=1);

namespace App\GraphQL\Exceptions;

use GraphQL\Error\ProvidesExtensions;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Nuwave\Lighthouse\Exceptions\ValidationException as LighthouseValidationException;

final class ValidationExceptionWithCode extends LighthouseValidationException implements ProvidesExtensions
{
    public const CODE = 'VALIDATION_ERROR';

    public function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), ['code' => self::CODE]);
    }

    public static function fromLaravel(LaravelValidationException $e): self
    {
        return new self($e->getMessage(), $e->validator);
    }
}
