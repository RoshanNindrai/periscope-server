<?php

declare(strict_types=1);

namespace App\GraphQL\ErrorHandlers;

use App\GraphQL\Exceptions\AuthenticationExceptionWithCode;
use GraphQL\Error\Error;
use Nuwave\Lighthouse\Execution\ErrorHandler;
use Periscope\AuthModule\Enums\AuthErrorCode;

final class AuthenticationErrorHandlerWithCode implements ErrorHandler
{
    public function __invoke(?Error $error, \Closure $next): ?array
    {
        if ($error === null) {
            return $next(null);
        }

        $underlying = $error->getPrevious();
        if ($underlying instanceof \Illuminate\Auth\AuthenticationException) {
            return $next(new Error(
                AuthErrorCode::UNAUTHORIZED->message(),
                $error->getNodes(),
                $error->getSource(),
                $error->getPositions(),
                $error->getPath(),
                AuthenticationExceptionWithCode::fromLaravel($underlying),
            ));
        }

        return $next($error);
    }
}
