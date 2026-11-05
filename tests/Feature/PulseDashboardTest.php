<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PulseDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'api.docs.username' => 'tools-user',
            'api.docs.password' => 'tools-secret',
        ]);
    }

    public function test_dashboard_requires_basic_auth(): void
    {
        $this->get('/pulse')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Basic realm="Internal tools", charset="UTF-8"');

        $this->withBasicAuth('tools-user', 'wrong')->get('/pulse')->assertUnauthorized();
    }

    public function test_dashboard_loads_with_valid_credentials(): void
    {
        $this->withBasicAuth('tools-user', 'tools-secret')
            ->get('/pulse')
            ->assertOk()
            ->assertSee('Pulse');
    }
}
