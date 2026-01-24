<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Exceptions;

use Periscope\AuthModule\Enums\AuthErrorCode;
use Throwable;

final class AuthModuleException extends \Exception
{
    private AuthErrorCode $authErrorCode;

    /** @var array<string, array<int, string>> */
    private array $errors;

    public function __construct(
        AuthErrorCode $authErrorCode,
        array $errors = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($authErrorCode->message(), 0, $previous);
        $this->authErrorCode = $authErrorCode;
        $this->errors = $errors;
    }

    public function getAuthErrorCode(): AuthErrorCode
    {
        return $this->authErrorCode;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
