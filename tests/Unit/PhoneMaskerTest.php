<?php

namespace Tests\Unit;

use Periscope\AuthModule\Support\PhoneMasker;
use PHPUnit\Framework\TestCase;

class PhoneMaskerTest extends TestCase
{
    public function test_masks_long_phone_revealing_last_four(): void
    {
        $masked = PhoneMasker::mask('+14155551234');
        $this->assertStringNotContainsString('415', $masked);
        $this->assertStringEndsWith('1234', $masked);
        $this->assertGreaterThan(4, strlen($masked));
    }

    public function test_masks_short_phone_fully(): void
    {
        $this->assertSame('****', PhoneMasker::mask('1234'));
        $this->assertSame('*', PhoneMasker::mask('1'));
    }

    public function test_returns_asterisks_for_null_or_empty(): void
    {
        $this->assertSame('****', PhoneMasker::mask(null));
        $this->assertSame('****', PhoneMasker::mask(''));
    }
}
