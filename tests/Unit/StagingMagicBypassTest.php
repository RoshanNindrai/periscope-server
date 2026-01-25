<?php

namespace Tests\Unit;

use App\Contracts\StagingMagicBypassInterface;
use App\Support\StagingBypassFeature;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StagingMagicBypassTest extends TestCase
{
    private StagingMagicBypassInterface $bypass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bypass = $this->app->make(StagingMagicBypassInterface::class);
    }

    public function test_allows_returns_true_when_staging_and_magic_matches(): void
    {
        $this->app->instance('env', 'staging');
        Config::set('staging-bypass.features.' . StagingBypassFeature::LOGIN_OTP, '999999');

        $this->assertTrue($this->bypass->allows(StagingBypassFeature::LOGIN_OTP, '999999'));
    }

    public function test_allows_returns_false_when_not_staging(): void
    {
        $this->app->instance('env', 'testing');
        Config::set('staging-bypass.features.' . StagingBypassFeature::LOGIN_OTP, '999999');

        $this->assertFalse($this->bypass->allows(StagingBypassFeature::LOGIN_OTP, '999999'));
    }

    public function test_allows_returns_false_when_provided_value_does_not_match(): void
    {
        $this->app->instance('env', 'staging');
        Config::set('staging-bypass.features.' . StagingBypassFeature::LOGIN_OTP, '999999');

        $this->assertFalse($this->bypass->allows(StagingBypassFeature::LOGIN_OTP, '000000'));
    }

    public function test_allows_returns_false_when_feature_not_configured(): void
    {
        $this->app->instance('env', 'staging');
        Config::set('staging-bypass.features.' . StagingBypassFeature::LOGIN_OTP, null);

        $this->assertFalse($this->bypass->allows(StagingBypassFeature::LOGIN_OTP, '999999'));
    }

    public function test_allows_returns_false_when_magic_is_empty_string(): void
    {
        $this->app->instance('env', 'staging');
        Config::set('staging-bypass.features.' . StagingBypassFeature::LOGIN_OTP, '');

        $this->assertFalse($this->bypass->allows(StagingBypassFeature::LOGIN_OTP, ''));
    }

    public function test_allows_returns_false_for_unknown_feature(): void
    {
        $this->app->instance('env', 'staging');
        Config::set('staging-bypass.features.' . StagingBypassFeature::LOGIN_OTP, '999999');

        $this->assertFalse($this->bypass->allows('nonexistent_feature', '999999'));
    }
}
