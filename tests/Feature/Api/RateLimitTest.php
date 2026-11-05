<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_over_the_limit_get_429_in_the_error_envelope(): void
    {
        config(['api.rate_limit_per_minute' => 2]);

        $this->getJson('/api/v1/vendors/count')->assertOk()->assertHeader('X-RateLimit-Limit', '2');
        $this->getJson('/api/v1/vendors/count')->assertOk();

        $this->getJson('/api/v1/vendors/count')
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('error.status', 429)
            ->assertJsonPath('error.code', 'too_many_requests');
    }
}
