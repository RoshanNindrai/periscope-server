<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Http\JsonResponse;

/**
 * Standardized JSON API response builder.
 * Works with any state enum (value, message()) and error enum (value, message(), statusCode()).
 *
 * Standard error format (REST): { "status": "<CODE>", "error": "<CODE>", "message": "<human message>", "errors": {...}? }
 * - status, error: same error code (e.g. UNAUTHORIZED, VALIDATION_ERROR, SEARCH_FAILED)
 * - message: human-readable description
 * - errors: optional; validation field errors (only for VALIDATION_ERROR)
 */
final class ApiResponse
{
    private function __construct() {}

    /**
     * @param  object  $state  Object with value and message() (e.g. AuthResponseState, SearchResponseState)
     * @param  array<string, mixed>  $data
     */
    public static function success(object $state, array $data = [], int $httpStatus = 200): JsonResponse
    {
        $payload = [
            'status' => $state->value,
            'message' => $state->message(),
        ];
        return response()->json(array_merge($payload, $data), $httpStatus);
    }

    /**
     * @param  object  $errorCode  Object with value, message(), statusCode() (e.g. AuthErrorCode, SearchErrorCode)
     * @param  array<string, array<int, string>>  $errors  Optional validation errors (e.g. ['field' => ['msg']])
     */
    public static function error(object $errorCode, array $errors = []): JsonResponse
    {
        $payload = [
            'status' => $errorCode->value,
            'error' => $errorCode->value,
            'message' => $errorCode->message(),
        ];
        if ($errors !== []) {
            $payload['errors'] = $errors;
        }
        return response()->json($payload, $errorCode->statusCode());
    }
}
