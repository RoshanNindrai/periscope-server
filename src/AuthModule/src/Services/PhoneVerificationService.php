<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Services;

use App\Contracts\UserRepositoryInterface;
use Carbon\Carbon;
use Periscope\AuthModule\Constants\AuthModuleConstants;
use Periscope\AuthModule\Contracts\PhoneHasherInterface;
use Periscope\AuthModule\Contracts\VerificationCodeGeneratorInterface;
use Periscope\AuthModule\Enums\AuthErrorCode;
use Periscope\AuthModule\Enums\AuthResponseState;
use Periscope\AuthModule\Exceptions\AuthModuleException;
use Periscope\AuthModule\Notifications\VerifyPhoneNotification;
use Periscope\AuthModule\Support\VerificationCodeRepositoryFactory;
use Throwable;

final class PhoneVerificationService
{
    public function __construct(
        private readonly PhoneHasherInterface $phoneHasher,
        private readonly VerificationCodeGeneratorInterface $codeGenerator,
        private readonly VerificationCodeRepositoryFactory $codeRepoFactory,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @return array{state: AuthResponseState, user: \Illuminate\Database\Eloquent\Model}
     *
     * @throws AuthModuleException
     * @throws Throwable
     */
    public function verifyPhone(string $phone, string $code): array
    {
        $phone = phone($phone)->formatE164();
        $user = $this->userRepository->findByPhoneHash($this->phoneHasher->hash($phone));

        if ($user === null) {
            throw new AuthModuleException(AuthErrorCode::USER_NOT_FOUND);
        }

        if ($user->hasVerifiedPhone()) {
            return ['state' => AuthResponseState::PHONE_ALREADY_VERIFIED, 'user' => $user];
        }

        $repo = $this->codeRepoFactory->forPhone();
        $record = $repo->find($phone);

        if ($record === null) {
            throw new AuthModuleException(AuthErrorCode::INVALID_VERIFICATION_CODE);
        }

        $createdAt = Carbon::parse($record->created_at);
        if (now()->diffInMinutes($createdAt) > AuthModuleConstants::CODE_EXPIRY_MINUTES) {
            $repo->delete($phone);
            throw new AuthModuleException(AuthErrorCode::EXPIRED_VERIFICATION_CODE);
        }

        if ((int) $record->attempts >= AuthModuleConstants::MAX_VERIFICATION_ATTEMPTS) {
            throw new AuthModuleException(AuthErrorCode::MAX_VERIFICATION_ATTEMPTS);
        }

        if (!hash_equals($record->code, $code)) {
            $repo->incrementAttempts($phone);
            throw new AuthModuleException(AuthErrorCode::INVALID_VERIFICATION_CODE);
        }

        if (!$user->markPhoneAsVerified()) {
            throw new AuthModuleException(AuthErrorCode::UNABLE_TO_VERIFY_PHONE);
        }

        $repo->delete($phone);

        return ['state' => AuthResponseState::PHONE_VERIFIED, 'user' => $user->fresh()];
    }

    /**
     * @return AuthResponseState::PHONE_ALREADY_VERIFIED | AuthResponseState::VERIFICATION_SMS_SENT
     *
     * @throws AuthModuleException
     * @throws Throwable
     */
    public function resendCode(object $user): AuthResponseState
    {
        if ($user->hasVerifiedPhone()) {
            return AuthResponseState::PHONE_ALREADY_VERIFIED;
        }

        $code = $this->codeGenerator->generate();
        $repo = $this->codeRepoFactory->forPhone();
        $repo->delete($user->phone);
        $repo->store($user->phone, $code);

        try {
            $user->notify(new VerifyPhoneNotification($code));
        } catch (Throwable $e) {
            throw new AuthModuleException(AuthErrorCode::VERIFICATION_SMS_SEND_FAILED);
        }

        return AuthResponseState::VERIFICATION_SMS_SENT;
    }
}
