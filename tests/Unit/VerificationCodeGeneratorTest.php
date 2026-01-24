<?php

namespace Tests\Unit;

use Periscope\AuthModule\Constants\AuthModuleConstants;
use Periscope\AuthModule\Support\VerificationCodeGenerator;
use PHPUnit\Framework\TestCase;

class VerificationCodeGeneratorTest extends TestCase
{
    public function test_generates_code_of_expected_length(): void
    {
        $gen = new VerificationCodeGenerator;
        $code = $gen->generate();
        $this->assertSame(AuthModuleConstants::CODE_LENGTH, strlen($code));
    }

    public function test_generates_numeric_string(): void
    {
        $gen = new VerificationCodeGenerator;
        $code = $gen->generate();
        $this->assertMatchesRegularExpression('/^\d{' . AuthModuleConstants::CODE_LENGTH . '}$/', $code);
    }

    public function test_generates_different_codes(): void
    {
        $gen = new VerificationCodeGenerator;
        $codes = array_map(fn () => $gen->generate(), range(1, 20));
        $this->assertCount(20, array_unique($codes));
    }
}
