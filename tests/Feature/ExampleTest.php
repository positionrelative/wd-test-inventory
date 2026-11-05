<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_ping_endpoint_returns_ok(): void
    {
        $this->getJson('/api/ping')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_frontend_page_loads(): void
    {
        $this->withoutVite()->get('/')->assertOk();
    }
}
