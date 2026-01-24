<?php

namespace Tests\Unit;

use App\Support\Http\ApiResponse;
use Periscope\AuthModule\Enums\AuthErrorCode;
use Periscope\AuthModule\Enums\AuthResponseState;
use Periscope\SearchModule\Enums\SearchErrorCode;
use Periscope\SearchModule\Enums\SearchResponseState;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_success_includes_status_and_message(): void
    {
        $response = ApiResponse::success(AuthResponseState::HEALTH_CHECK, ['x' => 1]);
        $data = json_decode($response->getContent(), true);
        $this->assertSame(AuthResponseState::HEALTH_CHECK->value, $data['status']);
        $this->assertSame(AuthResponseState::HEALTH_CHECK->message(), $data['message']);
        $this->assertSame(1, $data['x']);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_success_respects_http_status(): void
    {
        $response = ApiResponse::success(AuthResponseState::REGISTERED, [], 201);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_error_includes_status_error_message_and_status_code(): void
    {
        $response = ApiResponse::error(AuthErrorCode::UNAUTHORIZED);
        $data = json_decode($response->getContent(), true);
        $this->assertSame('UNAUTHORIZED', $data['status']);
        $this->assertSame('UNAUTHORIZED', $data['error']);
        $this->assertSame(AuthErrorCode::UNAUTHORIZED->message(), $data['message']);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_error_includes_validation_errors_when_provided(): void
    {
        $errors = ['phone' => ['The phone field is required.']];
        $response = ApiResponse::error(SearchErrorCode::VALIDATION_ERROR, $errors);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertSame($errors, $data['errors']);
    }
}
