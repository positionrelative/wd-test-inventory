<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\RequestId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

final class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_route_uses_the_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertExactJsonStructure(['error' => ['status', 'code', 'message', 'request_id']])
            ->assertJsonPath('error.status', 404)
            ->assertJsonPath('error.code', 'not_found');

        $this->assertSame($response->headers->get(RequestId::HEADER), $response->json('error.request_id'));
    }

    public function test_wrong_method_returns_405_with_allow_header(): void
    {
        $this->patchJson('/api/v1/assets')
            ->assertMethodNotAllowed()
            ->assertJsonPath('error.code', 'method_not_allowed')
            ->assertHeader('Allow');
    }

    public function test_unexpected_error_hides_details_without_debug(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/v1/test-boom', fn () => throw new RuntimeException('secret detail'));

        $this->getJson('/api/v1/test-boom')
            ->assertInternalServerError()
            ->assertJsonPath('error.code', 'server_error')
            ->assertJsonPath('error.message', 'An unexpected error occurred.')
            ->assertJsonMissingPath('error.debug')
            ->assertDontSee('secret detail');
    }

    public function test_unexpected_error_shows_details_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        Route::get('/api/v1/test-boom', fn () => throw new RuntimeException('secret detail'));

        $this->getJson('/api/v1/test-boom')
            ->assertInternalServerError()
            ->assertJsonPath('error.debug.exception', RuntimeException::class)
            ->assertJsonPath('error.debug.message', 'secret detail');
    }
}
