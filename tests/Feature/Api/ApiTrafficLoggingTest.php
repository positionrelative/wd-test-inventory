<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\RequestId;
use App\Models\ApiRequestLog;
use App\Models\AssetModel;
use App\Services\Logging\SensitiveDataMasker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ApiTrafficLoggingTest extends TestCase
{
    use RefreshDatabase;

    /** Regression: requests rejected by the framework's base middleware (here ValidatePostSize) are logged too. */
    public function test_request_rejected_by_base_middleware_is_logged_with_request_id(): void
    {
        $response = $this->call('POST', '/api/v1/assets', server: [
            'CONTENT_LENGTH' => (string) (1024 ** 4),
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response->assertStatus(413);
        $requestId = $response->headers->get(RequestId::HEADER);

        $this->assertNotNull($requestId);
        $this->assertSame($requestId, $response->json('error.request_id'));

        $log = ApiRequestLog::query()->sole();
        $this->assertSame(413, $log->response_status);
        $this->assertSame($requestId, $log->request_id);
    }

    public function test_large_response_is_masked_before_truncation(): void
    {
        config(['api_logging.max_body_length' => 200]);
        Route::get('/api/v1/test-large', fn () => response()->json([
            'token' => 'raw-secret-token',
            'items' => array_fill(0, 100, 'x'),
        ]));

        $this->getJson('/api/v1/test-large')->assertOk();

        $body = ApiRequestLog::query()->sole()->response_body;

        $this->assertIsString($body);
        $this->assertStringNotContainsString('raw-secret-token', $body);
        $this->assertStringContainsString(SensitiveDataMasker::MASK, $body);
        $this->assertStringEndsWith('...[truncated]', $body);
        $this->assertLessThanOrEqual(200 + strlen('...[truncated]'), strlen($body));
    }

    public function test_size_limit_applies_to_parsed_request_body(): void
    {
        config(['api_logging.max_body_length' => 32]);

        $this->postJson('/api/v1/assets', [
            'asset_tag' => str_repeat('A', 1000),
            'password' => 'hunter2',
        ]);

        $body = ApiRequestLog::query()->sole()->request_body;

        $this->assertIsString($body);
        $this->assertLessThanOrEqual(32 + strlen('...[truncated]'), strlen($body));
        $this->assertStringNotContainsString('hunter2', $body);
    }

    public function test_non_json_body_is_not_stored(): void
    {
        $this->call('POST', '/api/v1/assets', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
            'HTTP_ACCEPT' => 'application/json',
        ], 'token=raw-secret plain text');

        $body = ApiRequestLog::query()->sole()->request_body;

        $this->assertSame('[not stored: non-JSON body, 27 bytes]', $body);
    }

    public function test_request_and_response_are_logged(): void
    {
        $model = AssetModel::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer secret-token')
            ->postJson('/api/v1/assets?source=test', [
                'asset_tag' => 'AT-LOG',
                'model_id' => $model->id,
                'password' => 'hunter2',
            ])
            ->assertCreated();

        $log = ApiRequestLog::query()->sole();

        $this->assertSame($response->headers->get(RequestId::HEADER), $log->request_id);
        $this->assertSame('POST', $log->method);
        $this->assertSame('/api/v1/assets', $log->path);
        $this->assertSame(['source' => 'test'], $log->query);
        $this->assertSame(201, $log->response_status);
        $this->assertSame('AT-LOG', $log->request_body['asset_tag']);
        $this->assertSame(SensitiveDataMasker::MASK, $log->request_body['password']);
        $this->assertSame(SensitiveDataMasker::MASK, $log->request_headers['authorization']);
        $this->assertSame('AT-LOG', $log->response_body['data']['asset_tag']);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
    }

    public function test_error_responses_are_logged(): void
    {
        $this->getJson('/api/v1/does-not-exist')->assertNotFound();

        $log = ApiRequestLog::query()->sole();

        $this->assertSame(404, $log->response_status);
        $this->assertSame('not_found', $log->response_body['error']['code']);
    }

    public function test_non_api_requests_are_not_logged(): void
    {
        $this->withoutVite()->get('/')->assertOk();

        $this->assertDatabaseCount('api_request_logs', 0);
    }

    public function test_logging_can_be_disabled(): void
    {
        config(['api_logging.enabled' => false]);

        $this->getJson('/api/v1/ping')->assertOk();

        $this->assertDatabaseCount('api_request_logs', 0);
    }
}
