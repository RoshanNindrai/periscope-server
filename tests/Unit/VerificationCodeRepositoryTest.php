<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Periscope\AuthModule\Constants\AuthModuleConstants;
use Periscope\AuthModule\Support\VerificationCodeRepository;
use Tests\TestCase;

class VerificationCodeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): VerificationCodeRepository
    {
        return new VerificationCodeRepository(AuthModuleConstants::TABLE_LOGIN_VERIFICATION_CODES);
    }

    private function phoneHash(string $phone): string
    {
        return hash(AuthModuleConstants::PHONE_HASH_ALGO, $phone);
    }

    public function test_store_and_find(): void
    {
        $repo = $this->repo();
        $hash = $this->phoneHash('+15555551234');
        $repo->store($hash, '123456');
        $record = $repo->find($hash);
        $this->assertNotNull($record);
        $this->assertSame('123456', $record->code);
        $this->assertSame(0, (int) $record->attempts);
    }

    public function test_find_returns_null_when_missing(): void
    {
        $this->assertNull($this->repo()->find($this->phoneHash('+15555559999')));
    }

    public function test_delete_removes_record(): void
    {
        $repo = $this->repo();
        $hash = $this->phoneHash('+15555551234');
        $repo->store($hash, '123456');
        $repo->delete($hash);
        $this->assertNull($repo->find($hash));
    }

    public function test_increment_attempts(): void
    {
        $repo = $this->repo();
        $hash = $this->phoneHash('+15555551234');
        $repo->store($hash, '123456');
        $repo->incrementAttempts($hash);
        $repo->incrementAttempts($hash);
        $record = $repo->find($hash);
        $this->assertSame(2, (int) $record->attempts);
    }
}
