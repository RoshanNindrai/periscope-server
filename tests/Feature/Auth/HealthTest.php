<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_returns_200_and_expected_structure(): void
    {
        $r = $this->getJson('/api/health');
        $r->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'timestamp'])
            ->assertJsonPath('status', 'HEALTH_CHECK');
    }
}
