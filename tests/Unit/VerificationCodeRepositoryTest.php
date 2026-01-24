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

    public function test_store_and_find(): void
    {
        $repo = $this->repo();
        $repo->store('+15555551234', '123456');
        $record = $repo->find('+15555551234');
        $this->assertNotNull($record);
        $this->assertSame('123456', $record->code);
        $this->assertSame(0, (int) $record->attempts);
    }

    public function test_find_returns_null_when_missing(): void
    {
        $this->assertNull($this->repo()->find('+15555559999'));
    }

    public function test_delete_removes_record(): void
    {
        $repo = $this->repo();
        $repo->store('+15555551234', '123456');
        $repo->delete('+15555551234');
        $this->assertNull($repo->find('+15555551234'));
    }

    public function test_increment_attempts(): void
    {
        $repo = $this->repo();
        $repo->store('+15555551234', '123456');
        $repo->incrementAttempts('+15555551234');
        $repo->incrementAttempts('+15555551234');
        $record = $repo->find('+15555551234');
        $this->assertSame(2, (int) $record->attempts);
    }
}
