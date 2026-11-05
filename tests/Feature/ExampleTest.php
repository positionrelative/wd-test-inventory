<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_health_check_returns_ok(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_ping_endpoint_is_removed(): void
    {
        $this->getJson('/api/v1/ping')->assertNotFound();
    }

    public function test_frontend_page_loads(): void
    {
        $this->withoutVite()->get('/')->assertOk();
    }
}
