<?php

declare(strict_types=1);

namespace Periscope\AuthModule\Services;

use App\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Periscope\AuthModule\Contracts\PhoneHasherInterface;
use Periscope\AuthModule\Contracts\VerificationCodeGeneratorInterface;
use Periscope\AuthModule\Notifications\VerifyPhoneNotification;
use Periscope\AuthModule\Support\UsernameGenerator;
use Periscope\AuthModule\Support\VerificationCodeRepositoryFactory;
use Throwable;

final class RegistrationService
{
    public function __construct(
        private readonly string $tokenName,
        private readonly PhoneHasherInterface $phoneHasher,
        private readonly VerificationCodeGeneratorInterface $codeGenerator,
        private readonly VerificationCodeRepositoryFactory $codeRepoFactory,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UsernameGenerator $usernameGenerator,
    ) {}

    /**
     * @param  array{name: string, username?: string, phone: string}  $validated
     * @return array{user: \Illuminate\Contracts\Auth\Authenticatable, token: string}
     *
     * @throws Throwable
     */
    public function register(array $validated): array
    {
        $phone = phone($validated['phone'])->formatE164();

        // Generate username if not provided
        $username = $validated['username'] ?? null;
        if (empty($username)) {
            $username = $this->usernameGenerator->generateFromName(
                $validated['name'],
                $this->userRepository
            );
        }

        DB::beginTransaction();
        try {
            $user = $this->userRepository->create([
                'name' => $validated['name'],
                'username' => $username,
                'phone' => $phone,
            ]);

            $phoneHash = $this->phoneHasher->hash($phone);
            $code = $this->codeGenerator->generate();
            $repo = $this->codeRepoFactory->forPhone();
            $repo->delete($phoneHash);
            $repo->store($phoneHash, $code);

            try {
                $user->notify(new VerifyPhoneNotification($code));
            } catch (Throwable $e) {
                Log::warning('Failed to queue verification SMS', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $token = $user->createToken($this->tokenName)->plainTextToken;
            DB::commit();

            return ['user' => $user, 'token' => $token];
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
