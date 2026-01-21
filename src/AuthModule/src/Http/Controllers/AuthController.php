<?php

namespace Periscope\AuthModule\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Periscope\AuthModule\Notifications\VerifyPhoneNotification;
use Periscope\AuthModule\Notifications\LoginOtpNotification;
use Periscope\AuthModule\Enums\AuthResponseState;
use Periscope\AuthModule\Enums\AuthErrorCode;
use Illuminate\Validation\ValidationException;
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
     * Generate a random 6-digit verification code
     */
    protected function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
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
                'username' => 'required|string|min:3|max:30|unique:users|regex:/^[a-z0-9._]+$/',
                'phone' => ['required', 'string', 'phone:AUTO', 'unique:users'],
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

            // Format phone to E.164
            $phone = phone($validated['phone'])->formatE164();

            $user = $userModel::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'phone' => $phone,
            ]);

            // Generate and store verification code
            $code = $this->generateVerificationCode();
            
            // Delete any existing verification codes for this phone
            DB::table('phone_verification_codes')->where('phone', $user->phone)->delete();
            
            // Store new verification code
            DB::table('phone_verification_codes')->insert([
                'phone' => $user->phone,
                'code' => $code,
                'attempts' => 0,
                'created_at' => now(),
            ]);

            // Send phone verification notification (queued)
            try {
                $user->notify(new VerifyPhoneNotification($code));
            } catch (Throwable $e) {
                // Log but don't fail registration if SMS fails
                Log::warning('Failed to queue verification SMS', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
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
                'phone' => $validated['phone'] ?? null,
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
     * Send login OTP code to user's phone
     */
    public function login(Request $request): JsonResponse
    {
        $userModel = $this->getUserModel();
        
        try {
            $validated = $request->validate([
                'phone' => ['required', 'string', 'phone:AUTO'],
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
            // Format phone to E.164
            $phone = phone($validated['phone'])->formatE164();

            $user = $userModel::where('phone', $phone)->first();

            if (!$user) {
                $error = AuthErrorCode::USER_NOT_FOUND;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Generate and store login code
            $code = $this->generateVerificationCode();
            
            // Delete any existing login codes for this phone
            DB::table('login_verification_codes')->where('phone', $user->phone)->delete();
            
            // Store new login code
            DB::table('login_verification_codes')->insert([
                'phone' => $user->phone,
                'code' => $code,
                'attempts' => 0,
                'created_at' => now(),
            ]);

            // Send login OTP notification (queued)
            try {
                $user->notify(new LoginOtpNotification($code));
            } catch (Throwable $e) {
                Log::warning('Failed to queue login SMS', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                $error = AuthErrorCode::LOGIN_CODE_SEND_FAILED;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            $state = AuthResponseState::LOGIN_CODE_SENT;
            return response()->json([
                'status' => $state->value,
                'message' => $state->message(),
            ], 200);
        } catch (Throwable $e) {
            Log::error('Login code send failed', [
                'phone' => $validated['phone'] ?? null,
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
     * Verify login OTP code and authenticate user
     */
    public function verifyLogin(Request $request): JsonResponse
    {
        $userModel = $this->getUserModel();

        try {
            $validated = $request->validate([
                'phone' => ['required', 'string', 'phone:AUTO'],
                'code' => 'required|string|size:6',
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
            // Format phone to E.164
            $phone = phone($validated['phone'])->formatE164();

            // Get user
            $user = $userModel::where('phone', $phone)->first();
            
            if (!$user) {
                $error = AuthErrorCode::USER_NOT_FOUND;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Get login code record
            $codeRecord = DB::table('login_verification_codes')
                ->where('phone', $phone)
                ->first();

            if (!$codeRecord) {
                $error = AuthErrorCode::INVALID_LOGIN_CODE;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Check if code has expired (10 minutes)
            $createdAt = \Carbon\Carbon::parse($codeRecord->created_at);
            if (now()->diffInMinutes($createdAt) > 10) {
                // Delete expired code
                DB::table('login_verification_codes')->where('phone', $phone)->delete();
                
                $error = AuthErrorCode::EXPIRED_LOGIN_CODE;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Check if max attempts reached
            if ($codeRecord->attempts >= 5) {
                $error = AuthErrorCode::MAX_LOGIN_ATTEMPTS;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Verify code matches (constant-time comparison to prevent timing attacks)
            if (!hash_equals($codeRecord->code, $request->code)) {
                // Increment attempts
                DB::table('login_verification_codes')
                    ->where('phone', $phone)
                    ->increment('attempts');
                
                $error = AuthErrorCode::INVALID_LOGIN_CODE;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Code is valid - create session token
            $token = $user->createToken($this->getTokenName())->plainTextToken;

            // Delete the login code
            DB::table('login_verification_codes')->where('phone', $phone)->delete();

            $state = AuthResponseState::LOGGED_IN;
            return response()->json([
                'status' => $state->value,
                'message' => $state->message(),
                'user' => $user,
                'token' => $token,
            ], 200);
        } catch (Throwable $e) {
            Log::error('Login verification failed', [
                'phone' => $validated['phone'] ?? null,
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
     * Verify phone number with 6-digit code
     */
    public function verifyPhone(Request $request): JsonResponse
    {
        $userModel = $this->getUserModel();

        try {
            $validated = $request->validate([
                'phone' => ['required', 'string', 'phone:AUTO'],
                'code' => 'required|string|size:6',
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
            // Format phone to E.164
            $phone = phone($validated['phone'])->formatE164();

            // Get user
            $user = $userModel::where('phone', $phone)->first();
            
            if (!$user) {
                $error = AuthErrorCode::USER_NOT_FOUND;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Check if already verified
            if ($user->hasVerifiedPhone()) {
                $state = AuthResponseState::PHONE_ALREADY_VERIFIED;
                return response()->json([
                    'status' => $state->value,
                    'message' => $state->message(),
                ], 200);
            }

            // Get verification code record
            $codeRecord = DB::table('phone_verification_codes')
                ->where('phone', $phone)
                ->first();

            if (!$codeRecord) {
                $error = AuthErrorCode::INVALID_VERIFICATION_CODE;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Check if code has expired (10 minutes)
            $createdAt = \Carbon\Carbon::parse($codeRecord->created_at);
            if (now()->diffInMinutes($createdAt) > 10) {
                // Delete expired code
                DB::table('phone_verification_codes')->where('phone', $phone)->delete();
                
                $error = AuthErrorCode::EXPIRED_VERIFICATION_CODE;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Check if max attempts reached
            if ($codeRecord->attempts >= 5) {
                $error = AuthErrorCode::MAX_VERIFICATION_ATTEMPTS;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Verify code matches (constant-time comparison to prevent timing attacks)
            if (!hash_equals($codeRecord->code, $request->code)) {
                // Increment attempts
                DB::table('phone_verification_codes')
                    ->where('phone', $phone)
                    ->increment('attempts');
                
                $error = AuthErrorCode::INVALID_VERIFICATION_CODE;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            // Code is valid - mark phone as verified
            if ($user->markPhoneAsVerified()) {
                // Delete the verification code
                DB::table('phone_verification_codes')->where('phone', $phone)->delete();
                
                $state = AuthResponseState::PHONE_VERIFIED;
                return response()->json([
                    'status' => $state->value,
                    'message' => $state->message(),
                    'user' => $user->fresh(),
                ], 200);
            }

            $error = AuthErrorCode::UNABLE_TO_VERIFY_PHONE;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        } catch (Throwable $e) {
            Log::error('Phone verification failed', [
                'phone' => $validated['phone'] ?? null,
                'error' => $e->getMessage(),
            ]);

            $error = AuthErrorCode::PHONE_VERIFICATION_FAILED;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        }
    }

    /**
     * Resend phone verification code
     */
    public function resendVerificationSms(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->hasVerifiedPhone()) {
                $state = AuthResponseState::PHONE_ALREADY_VERIFIED;
                return response()->json([
                    'status' => $state->value,
                    'message' => $state->message(),
                ], 200);
            }

            // Generate new verification code
            $code = $this->generateVerificationCode();
            
            // Delete old verification code (invalidate previous)
            DB::table('phone_verification_codes')->where('phone', $user->phone)->delete();
            
            // Store new verification code
            DB::table('phone_verification_codes')->insert([
                'phone' => $user->phone,
                'code' => $code,
                'attempts' => 0,
                'created_at' => now(),
            ]);

            // Send SMS with new code
            try {
                $user->notify(new VerifyPhoneNotification($code));
            } catch (Throwable $e) {
                Log::warning('Failed to queue verification SMS', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                $error = AuthErrorCode::VERIFICATION_SMS_SEND_FAILED;
                return response()->json([
                    'status' => $error->value,
                    'error' => $error->value,
                    'message' => $error->message(),
                ], $error->statusCode());
            }

            $state = AuthResponseState::VERIFICATION_SMS_SENT;
            return response()->json([
                'status' => $state->value,
                'message' => $state->message(),
            ], 200);
        } catch (Throwable $e) {
            Log::error('Resend verification SMS failed', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            $error = AuthErrorCode::VERIFICATION_SMS_SEND_FAILED;
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
