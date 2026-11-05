<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\RequestId;
use App\Models\ApiRequestLog;
use App\Models\AssetModel;
use App\Services\Logging\SensitiveDataMasker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApiTrafficLoggingTest extends TestCase
{
    use RefreshDatabase;

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
