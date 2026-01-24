<?php

declare(strict_types=1);

namespace App\GraphQL\ErrorHandlers;

use App\GraphQL\Exceptions\AuthorizationExceptionWithCode;
use GraphQL\Error\Error;
use Illuminate\Auth\Access\AuthorizationException as LaravelAuthorizationException;
use Nuwave\Lighthouse\Execution\ErrorHandler;
use Periscope\AuthModule\Enums\AuthErrorCode;

final class AuthorizationErrorHandlerWithCode implements ErrorHandler
{
    public function __invoke(?Error $error, \Closure $next): ?array
    {
        if ($error === null) {
            return $next(null);
        }

        $underlying = $error->getPrevious();
        if ($underlying instanceof LaravelAuthorizationException) {
            return $next(new Error(
                AuthErrorCode::FORBIDDEN->message(),
                $error->getNodes(),
                $error->getSource(),
                $error->getPositions(),
                $error->getPath(),
                AuthorizationExceptionWithCode::fromLaravel($underlying),
            ));
        }

        return $next($error);
    }
}
