<?php

declare(strict_types=1);

namespace App\GraphQL\ErrorHandlers;

use App\GraphQL\Exceptions\ValidationExceptionWithCode;
use GraphQL\Error\Error;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Nuwave\Lighthouse\Execution\ErrorHandler;
use Periscope\AuthModule\Enums\AuthErrorCode;

final class ValidationErrorHandlerWithCode implements ErrorHandler
{
    public function __invoke(?Error $error, \Closure $next): ?array
    {
        if ($error === null) {
            return $next(null);
        }

        $underlying = $error->getPrevious();
        if ($underlying instanceof LaravelValidationException) {
            return $next(new Error(
                AuthErrorCode::VALIDATION_ERROR->message(),
                $error->getNodes(),
                $error->getSource(),
                $error->getPositions(),
                $error->getPath(),
                ValidationExceptionWithCode::fromLaravel($underlying),
            ));
        }

        return $next($error);
    }
}
