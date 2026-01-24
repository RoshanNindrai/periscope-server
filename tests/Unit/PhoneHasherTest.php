<?php

namespace Tests\Unit;

use Periscope\AuthModule\Support\PhoneHasher;
use PHPUnit\Framework\TestCase;

class PhoneHasherTest extends TestCase
{
    public function test_hash_is_deterministic(): void
    {
        $hasher = new PhoneHasher;
        $phone = '+14155551234';
        $this->assertSame($hasher->hash($phone), $hasher->hash($phone));
    }

    public function test_different_phones_produce_different_hashes(): void
    {
        $hasher = new PhoneHasher;
        $this->assertNotSame(
            $hasher->hash('+14155551234'),
            $hasher->hash('+14155551235')
        );
    }

    public function test_hash_is_64_char_sha256_hex(): void
    {
        $hasher = new PhoneHasher;
        $hash = $hasher->hash('+15555551234');
        $this->assertSame(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }
}
