<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Http\Controllers;

use App\Contracts\UserRepositoryInterface;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Periscope\AuthModule\Constants\AuthModuleConstants;
use Periscope\AuthModule\Contracts\PhoneHasherInterface;
use Periscope\AuthModule\Enums\AuthErrorCode;
use Periscope\AuthModule\Enums\AuthResponseState;
use Periscope\AuthModule\Exceptions\AuthModuleException;
use Periscope\AuthModule\Services\LoginOtpService;
use Periscope\AuthModule\Services\PhoneVerificationService;
use Periscope\AuthModule\Services\RegistrationService;
use Periscope\AuthModule\Support\PhoneMasker;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly LoginOtpService $loginOtpService,
        private readonly PhoneVerificationService $phoneVerificationService,
        private readonly PhoneHasherInterface $phoneHasher,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'nullable|string|min:3|max:30|unique:users|regex:/^[a-z0-9._]+$/',
                'phone' => [
                    'required',
                    'string',
                    'phone:AUTO',
                    function (string $attr, mixed $value, \Closure $fail): void {
                        $p = phone($value)->formatE164();
                        if ($this->userRepository->existsByPhoneHash($this->phoneHasher->hash($p))) {
                            $fail(__('validation.unique', ['attribute' => 'phone']));
                        }
                    },
                ],
            ]);
        } catch (ValidationException $e) {
            return ApiResponse::error(AuthErrorCode::VALIDATION_ERROR, $e->errors());
        }

        try {
            $result = $this->registrationService->register($validated);
            return ApiResponse::success(AuthResponseState::REGISTERED, [
                'user' => $result['user'],
                'token' => $result['token'],
            ], 201);
        } catch (Throwable $e) {
            Log::error('User registration failed', [
                'phone' => PhoneMasker::mask($validated['phone'] ?? null),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            report($e);
            return ApiResponse::error(AuthErrorCode::REGISTRATION_FAILED);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => ['required', 'string', 'phone:AUTO'],
            ]);
        } catch (ValidationException $e) {
            return ApiResponse::error(AuthErrorCode::VALIDATION_ERROR, $e->errors());
        }

        try {
            $this->loginOtpService->sendOtp($validated['phone']);
            return ApiResponse::success(AuthResponseState::LOGIN_CODE_SENT);
        } catch (AuthModuleException $e) {
            return ApiResponse::error($e->getAuthErrorCode(), $e->getErrors());
        } catch (Throwable $e) {
            Log::error('Login code send failed', [
                'phone' => PhoneMasker::mask($validated['phone'] ?? null),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error(AuthErrorCode::LOGIN_FAILED);
        }
    }

    public function verifyLogin(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => ['required', 'string', 'phone:AUTO'],
                'code' => 'required|string|size:' . AuthModuleConstants::CODE_LENGTH,
            ]);
        } catch (ValidationException $e) {
            return ApiResponse::error(AuthErrorCode::VALIDATION_ERROR, $e->errors());
        }

        try {
            $result = $this->loginOtpService->verify($validated['phone'], $validated['code']);
            return ApiResponse::success(AuthResponseState::LOGGED_IN, [
                'user' => $result['user'],
                'token' => $result['token'],
            ]);
        } catch (AuthModuleException $e) {
            return ApiResponse::error($e->getAuthErrorCode(), $e->getErrors());
        } catch (Throwable $e) {
            Log::error('Login verification failed', [
                'phone' => PhoneMasker::mask($validated['phone'] ?? null),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error(AuthErrorCode::LOGIN_FAILED);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return ApiResponse::success(AuthResponseState::LOGGED_OUT);
        } catch (Throwable $e) {
            Log::error('Logout failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error(AuthErrorCode::LOGOUT_FAILED);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            return ApiResponse::success(AuthResponseState::USER_RETRIEVED, [
                'user' => $request->user(),
            ]);
        } catch (Throwable $e) {
            Log::error('Get user failed', ['error' => $e->getMessage()]);
            return ApiResponse::error(AuthErrorCode::USER_RETRIEVAL_FAILED);
        }
    }

    public function verifyPhone(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => ['required', 'string', 'phone:AUTO'],
                'code' => 'required|string|size:' . AuthModuleConstants::CODE_LENGTH,
            ]);
        } catch (ValidationException $e) {
            return ApiResponse::error(AuthErrorCode::VALIDATION_ERROR, $e->errors());
        }

        try {
            $result = $this->phoneVerificationService->verifyPhone($validated['phone'], $validated['code']);
            return ApiResponse::success($result['state'], ['user' => $result['user']]);
        } catch (AuthModuleException $e) {
            return ApiResponse::error($e->getAuthErrorCode(), $e->getErrors());
        } catch (Throwable $e) {
            Log::error('Phone verification failed', [
                'phone' => PhoneMasker::mask($validated['phone'] ?? null),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error(AuthErrorCode::PHONE_VERIFICATION_FAILED);
        }
    }

    public function resendVerificationSms(Request $request): JsonResponse
    {
        try {
            $state = $this->phoneVerificationService->resendCode($request->user());
            return ApiResponse::success($state);
        } catch (AuthModuleException $e) {
            return ApiResponse::error($e->getAuthErrorCode(), $e->getErrors());
        } catch (Throwable $e) {
            Log::error('Resend verification SMS failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error(AuthErrorCode::VERIFICATION_SMS_SEND_FAILED);
        }
    }

    public function healthCheck(): JsonResponse
    {
        return ApiResponse::success(AuthResponseState::HEALTH_CHECK, [
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
