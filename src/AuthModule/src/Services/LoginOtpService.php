<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Services;

use App\Contracts\UserRepositoryInterface;
use Carbon\Carbon;
use Periscope\AuthModule\Constants\AuthModuleConstants;
use Periscope\AuthModule\Contracts\PhoneHasherInterface;
use Periscope\AuthModule\Contracts\VerificationCodeGeneratorInterface;
use Periscope\AuthModule\Enums\AuthErrorCode;
use Periscope\AuthModule\Exceptions\AuthModuleException;
use Periscope\AuthModule\Notifications\LoginOtpNotification;
use Periscope\AuthModule\Support\VerificationCodeRepositoryFactory;
use Throwable;

final class LoginOtpService
{
    public function __construct(
        private readonly string $tokenName,
        private readonly PhoneHasherInterface $phoneHasher,
        private readonly VerificationCodeGeneratorInterface $codeGenerator,
        private readonly VerificationCodeRepositoryFactory $codeRepoFactory,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @throws AuthModuleException
     * @throws Throwable
     */
    public function sendOtp(string $phone): void
    {
        $phone = phone($phone)->formatE164();
        $user = $this->userRepository->findByPhoneHash($this->phoneHasher->hash($phone));

        if ($user === null) {
            throw new AuthModuleException(AuthErrorCode::USER_NOT_FOUND);
        }

        $phoneHash = $this->phoneHasher->hash($phone);
        $code = $this->codeGenerator->generate();
        $repo = $this->codeRepoFactory->forLogin();
        $repo->delete($phoneHash);
        $repo->store($phoneHash, $code);

        try {
            $user->notify(new LoginOtpNotification($code));
        } catch (Throwable $e) {
            throw new AuthModuleException(AuthErrorCode::LOGIN_CODE_SEND_FAILED);
        }
    }

    /**
     * @return array{user: \Illuminate\Contracts\Auth\Authenticatable, token: string}
     *
     * @throws AuthModuleException
     * @throws Throwable
     */
    public function verify(string $phone, string $code): array
    {
        $phone = phone($phone)->formatE164();
        $user = $this->userRepository->findByPhoneHash($this->phoneHasher->hash($phone));

        if ($user === null) {
            throw new AuthModuleException(AuthErrorCode::USER_NOT_FOUND);
        }

        $phoneHash = $this->phoneHasher->hash($phone);
        $repo = $this->codeRepoFactory->forLogin();
        $record = $repo->find($phoneHash);

        if ($record === null) {
            throw new AuthModuleException(AuthErrorCode::INVALID_LOGIN_CODE);
        }

        $createdAt = Carbon::parse($record->created_at);
        if (now()->diffInMinutes($createdAt) > AuthModuleConstants::CODE_EXPIRY_MINUTES) {
            $repo->delete($phoneHash);
            throw new AuthModuleException(AuthErrorCode::EXPIRED_LOGIN_CODE);
        }

        if ((int) $record->attempts >= AuthModuleConstants::MAX_VERIFICATION_ATTEMPTS) {
            throw new AuthModuleException(AuthErrorCode::MAX_LOGIN_ATTEMPTS);
        }

        if (!hash_equals($record->code, $code)) {
            $repo->incrementAttempts($phoneHash);
            throw new AuthModuleException(AuthErrorCode::INVALID_LOGIN_CODE);
        }

        $token = $user->createToken($this->tokenName)->plainTextToken;
        $repo->delete($phoneHash);

        return ['user' => $user, 'token' => $token];
    }
}
