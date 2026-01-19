<?php

namespace Periscope\AuthModule\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Periscope\AuthModule\Notifications\PasswordResetAttemptNotification;
use Periscope\AuthModule\Enums\AuthResponseState;
use Periscope\AuthModule\Enums\AuthErrorCode;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Throwable;

class AuthController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get the user model class name from config
     */
    protected function getUserModel(): string
    {
        return config('auth-module.user_model', \App\Models\User::class);
    }

    /**
     * Get the token name from config
     */
    protected function getTokenName(): string
    {
        return config('auth-module.token_name', 'periscope-auth-token');
    }

    /**
     * Get password validation rules
     */
    protected function getPasswordRules(): array
    {
        $rules = ['required', 'string', 'confirmed'];
        
        $minLength = config('auth-module.password_min_length', 8);
        $rules[] = "min:{$minLength}";
        
        if (config('auth-module.password_require_uppercase', false)) {
            $rules[] = 'regex:/[A-Z]/';
        }
        
        if (config('auth-module.password_require_lowercase', false)) {
            $rules[] = 'regex:/[a-z]/';
        }
        
        if (config('auth-module.password_require_numbers', false)) {
            $rules[] = 'regex:/[0-9]/';
        }
        
        if (config('auth-module.password_require_symbols', false)) {
            $rules[] = 'regex:/[^A-Za-z0-9]/';
        }
        
        return $rules;
    }

    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $userModel = $this->getUserModel();
        
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => $this->getPasswordRules(),
            ]);
        } catch (ValidationException $e) {
            $error = AuthErrorCode::VALIDATION_ERROR;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
                'errors' => $e->errors(),
            ], $error->statusCode());
        }

        try {
            DB::beginTransaction();

            $user = $userModel::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Send email verification notification (queued)
            if (method_exists($user, 'sendEmailVerificationNotification')) {
                try {
                    $user->sendEmailVerificationNotification();
                } catch (Throwable $e) {
                    // Log but don't fail registration if email fails
                    Log::warning('Failed to queue verification email', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $token = $user->createToken($this->getTokenName())->plainTextToken;

            DB::commit();

            $state = AuthResponseState::REGISTERED;
            return response()->json([
                'status' => $state->value,
                'message' => $state->message(),
                'user' => $user,
                'token' => $token,
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('User registration failed', [
                'email' => $validated['email'] ?? null,
                'error' => $e->getMessage(),
            ]);

            $error = AuthErrorCode::REGISTRATION_FAILED;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        }
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $userModel = $this->getUserModel();
        
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            $error = AuthErrorCode::VALIDATION_ERROR;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
                'errors' => $e->errors(),
            ], $error->statusCode());
        }

        try {
            $user = $userModel::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                $error = AuthErrorCode::INVALID_CREDENTIALS;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                    'errors' => [
                        'email' => [$error->message()],
                    ],
                ], $error->statusCode());
            }

            // Check if account is locked
            if (method_exists($user, 'isLocked') && $user->isLocked()) {
                $error = AuthErrorCode::ACCOUNT_LOCKED;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            $token = $user->createToken($this->getTokenName())->plainTextToken;

            $state = AuthResponseState::LOGGED_IN;
            return response()->json([
                'status' => $state->value,
                'message' => $state->message(),
                'user' => $user,
                'token' => $token,
            ], 200);
        } catch (ValidationException $e) {
            $error = AuthErrorCode::VALIDATION_ERROR;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
                'errors' => $e->errors(),
            ], $error->statusCode());
        } catch (Throwable $e) {
            Log::error('Login failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            $error = AuthErrorCode::LOGIN_FAILED;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            $state = AuthResponseState::LOGGED_OUT;
            return response()->json([
                'status' => $state->value,
                'message' => $state->message(),
            ], 200);
        } catch (Throwable $e) {
            Log::error('Logout failed', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            $error = AuthErrorCode::LOGOUT_FAILED;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $state = AuthResponseState::USER_RETRIEVED;
            return response()->json([
                'status' => $state->value,
                'message' => $state->message(),
                'user' => $request->user(),
            ], 200);
        } catch (Throwable $e) {
            Log::error('Get user failed', [
                'error' => $e->getMessage(),
            ]);

            $error = AuthErrorCode::USER_RETRIEVAL_FAILED;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        }
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);
        } catch (ValidationException $e) {
            $error = AuthErrorCode::VALIDATION_ERROR;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
                'errors' => $e->errors(),
            ], $error->statusCode());
        }

        try {
            Password::sendResetLink(
                $request->only('email')
            );

            // Always return success to prevent email enumeration
            // Laravel's Password facade handles the case where user doesn't exist
            $state = AuthResponseState::PASSWORD_RESET_LINK_SENT;
            return response()->json([
                'status' => $state->value,
                'message' => $state->message(),
            ], 200);
        } catch (Throwable $e) {
            Log::error('Password reset request failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            // Still return success to prevent email enumeration
            $state = AuthResponseState::PASSWORD_RESET_LINK_SENT;
            return response()->json([
                'status' => $state->value,
                'message' => $state->message(),
            ], 200);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $userModel = $this->getUserModel();
        
        try {
            $request->validate([
                'token' => 'required|string',
                'email' => 'required|email',
                'password' => $this->getPasswordRules(),
            ]);
        } catch (ValidationException $e) {
            $error = AuthErrorCode::VALIDATION_ERROR;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
                'errors' => $e->errors(),
            ], $error->statusCode());
        }

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, string $password) {
                    $user->password = Hash::make($password);
                    $user->save();

                    // Generate lock token for security notification
                    $lockToken = Str::random(64);

                    // Send password reset attempt notification (queued)
                    // This email asks user to confirm if they initiated the reset
                    try {
                        $user->notify(new PasswordResetAttemptNotification($lockToken));
                    } catch (Throwable $e) {
                        // Log but don't fail password reset if email fails
                        Log::warning('Failed to queue password reset attempt notification', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                $state = AuthResponseState::PASSWORD_RESET;
                return response()->json([
                    'status' => $state->value,
                    'message' => $state->message(),
                ], 200);
            }

            // Handle different failure cases
            if ($status === Password::INVALID_TOKEN) {
                $error = AuthErrorCode::INVALID_RESET_TOKEN;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            if ($status === Password::INVALID_USER) {
                $error = AuthErrorCode::INVALID_USER;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            $error = AuthErrorCode::PASSWORD_RESET_FAILED;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        } catch (Throwable $e) {
            Log::error('Password reset failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            $error = AuthErrorCode::PASSWORD_RESET_FAILED;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        }
    }

    /**
     * Verify email address
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $userModel = $this->getUserModel();

        try {
            $request->validate([
                'id' => 'required|integer',
                'hash' => 'required|string',
                'expires' => 'required|integer',
                'signature' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            $error = AuthErrorCode::VALIDATION_ERROR;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
                'errors' => $e->errors(),
            ], $error->statusCode());
        }

        try {
            // Verify signature
            $expectedSignature = hash_hmac('sha256', $request->id . '|' . $request->hash . '|' . $request->expires, config('app.key'));
            if (!hash_equals($expectedSignature, $request->signature)) {
                $error = AuthErrorCode::INVALID_VERIFICATION_SIGNATURE;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Check if expired
            if (now()->timestamp > $request->expires) {
                $error = AuthErrorCode::EXPIRED_VERIFICATION_LINK;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Get user
            try {
                $user = $userModel::findOrFail($request->id);
            } catch (ModelNotFoundException $e) {
                $error = AuthErrorCode::USER_NOT_FOUND;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Verify hash matches email
            if (!hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
                $error = AuthErrorCode::INVALID_VERIFICATION_LINK;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            if ($user->hasVerifiedEmail()) {
                $state = AuthResponseState::EMAIL_ALREADY_VERIFIED;
                return response()->json([
                    'status' => $state->value,
                    'message' => $state->message(),
                ], 200);
            }

            if ($user->markEmailAsVerified()) {
                $state = AuthResponseState::EMAIL_VERIFIED;
                return response()->json([
                    'status' => $state->value,
                    'message' => $state->message(),
                    'user' => $user->fresh(),
                ], 200);
            }

            $error = AuthErrorCode::UNABLE_TO_VERIFY_EMAIL;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        } catch (Throwable $e) {
            Log::error('Email verification failed', [
                'user_id' => $request->id ?? null,
                'error' => $e->getMessage(),
            ]);

            $error = AuthErrorCode::EMAIL_VERIFICATION_FAILED;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        }
    }

    /**
     * Resend email verification notification
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->hasVerifiedEmail()) {
                $state = AuthResponseState::EMAIL_ALREADY_VERIFIED;
                return response()->json([
                    'status' => $state->value,
                    'message' => $state->message(),
                ], 200);
            }

            if (method_exists($user, 'sendEmailVerificationNotification')) {
                try {
                    $user->sendEmailVerificationNotification();
                } catch (Throwable $e) {
                    Log::warning('Failed to queue verification email', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);

                    $error = AuthErrorCode::VERIFICATION_EMAIL_SEND_FAILED;
                    return response()->json([
                        'status' => $error->value,
                        'error' => $error->value,
                        'message' => $error->message(),
                    ], $error->statusCode());
                }
            }

            $state = AuthResponseState::VERIFICATION_EMAIL_SENT;
            return response()->json([
                'status' => $state->value,
                'message' => $state->message(),
            ], 200);
        } catch (Throwable $e) {
            Log::error('Resend verification email failed', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            $error = AuthErrorCode::VERIFICATION_EMAIL_SEND_FAILED;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        }
    }

    /**
     * Lock account after password reset if user didn't initiate it
     */
    public function lockAccount(Request $request): JsonResponse
    {
        $userModel = $this->getUserModel();

        try {
            $request->validate([
                'id' => 'required|integer',
                'token' => 'required|string',
                'expires' => 'required|integer',
                'signature' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            $error = AuthErrorCode::VALIDATION_ERROR;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
                'errors' => $e->errors(),
            ], $error->statusCode());
        }

        try {
            // Verify signature
            $expectedSignature = hash_hmac('sha256', $request->id . '|' . $request->token . '|' . $request->expires, config('app.key'));
            if (!hash_equals($expectedSignature, $request->signature)) {
                $error = AuthErrorCode::INVALID_LOCK_SIGNATURE;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Check if expired
            if (now()->timestamp > $request->expires) {
                $error = AuthErrorCode::EXPIRED_LOCK_LINK;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Get user
            try {
                $user = $userModel::findOrFail($request->id);
            } catch (ModelNotFoundException $e) {
                $error = AuthErrorCode::USER_NOT_FOUND;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Check if already locked
            if ($user->isLocked()) {
                $state = AuthResponseState::ACCOUNT_ALREADY_LOCKED;
                return response()->json([
                    'status' => $state->value,
                    'message' => $state->message(),
                ], 200);
            }

            // Lock the account
            if ($user->lockAccount()) {
                // Revoke all tokens for security
                $user->tokens()->delete();

                Log::warning('Account locked due to unauthorized password reset', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                $state = AuthResponseState::ACCOUNT_LOCKED;
                return response()->json([
                    'status' => $state->value,
                    'message' => $state->message(),
                ], 200);
            }

            $error = AuthErrorCode::UNABLE_TO_LOCK_ACCOUNT;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        } catch (Throwable $e) {
            Log::error('Account lock failed', [
                'user_id' => $request->id ?? null,
                'error' => $e->getMessage(),
            ]);

            $error = AuthErrorCode::ACCOUNT_LOCK_FAILED;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        }
    }

    /**
     * Health check endpoint
     */
    public function healthCheck(): JsonResponse
    {
        $state = AuthResponseState::HEALTH_CHECK;
        return response()->json([
            'status' => $state->value,
            'message' => $state->message(),
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}
