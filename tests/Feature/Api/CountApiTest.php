<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Contract;
use App\Models\Manufacturer;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_are_returned_in_a_resource(): void
    {
        Asset::factory()->count(3)->create();
        Contract::factory()->count(2)->create();

        $this->getJson('/api/v1/assets/count')
            ->assertOk()
            ->assertExactJson(['data' => ['count' => 3]]);
        $this->getJson('/api/v1/contracts/count')->assertExactJson(['data' => ['count' => 2]]);
        // 3 assets → 3 models → 3 manufacturers; 2 contracts → 2 vendors
        $this->getJson('/api/v1/manufacturers/count')->assertExactJson(['data' => ['count' => Manufacturer::query()->count()]]);
        $this->getJson('/api/v1/vendors/count')->assertExactJson(['data' => ['count' => Vendor::query()->count()]]);
    }

    public function test_contract_assets_count(): void
    {
        $contract = Contract::factory()->create();
        $contract->assets()->attach(Asset::factory()->count(2)->create());
        Asset::factory()->create(); // not on the contract

        $this->getJson("/api/v1/contracts/{$contract->id}/assets/count")
            ->assertOk()
            ->assertExactJson(['data' => ['count' => 2]]);

        $this->getJson('/api/v1/contracts/999/assets/count')->assertNotFound();
    }

    public function test_count_is_cached(): void
    {
        Asset::factory()->count(2)->create();
        $this->getJson('/api/v1/assets/count')->assertExactJson(['data' => ['count' => 2]]);

        // Changes outside the API show up only after the TTL...
        Asset::factory()->create();
        DB::enableQueryLog();
        $this->getJson('/api/v1/assets/count')->assertExactJson(['data' => ['count' => 2]]);
        $countQueries = array_filter(
            array_column(DB::getQueryLog(), 'query'),
            static fn (string $sql) => stripos($sql, 'count(') !== false,
        );
        DB::disableQueryLog();

        // ...and are served from the cache, without a COUNT query.
        $this->assertEmpty($countQueries);
    }

    public function test_api_writes_refresh_the_cached_counts(): void
    {
        $this->getJson('/api/v1/assets/count')->assertExactJson(['data' => ['count' => 0]]);
        $this->getJson('/api/v1/contracts/count')->assertExactJson(['data' => ['count' => 0]]);

        $asset = $this->postJson('/api/v1/assets', [
            'asset_tag' => 'AT-COUNT',
            'model_id' => AssetModel::factory()->create()->id,
        ])->assertCreated()->json('data.id');
        $this->getJson('/api/v1/assets/count')->assertExactJson(['data' => ['count' => 1]]);

        $contract = Contract::factory()->create();
        $this->getJson("/api/v1/contracts/{$contract->id}/assets/count")->assertExactJson(['data' => ['count' => 0]]);

        $this->putJson("/api/v1/contracts/{$contract->id}/assets/{$asset}")->assertNoContent();
        $this->getJson("/api/v1/contracts/{$contract->id}/assets/count")->assertExactJson(['data' => ['count' => 1]]);

        $this->deleteJson("/api/v1/contracts/{$contract->id}/assets/{$asset}")->assertNoContent();
        $this->getJson("/api/v1/contracts/{$contract->id}/assets/count")->assertExactJson(['data' => ['count' => 0]]);

        $this->deleteJson("/api/v1/assets/{$asset}")->assertNoContent();
        $this->getJson('/api/v1/assets/count')->assertExactJson(['data' => ['count' => 0]]);
    }

    public function test_count_route_does_not_shadow_numeric_ids(): void
    {
        $asset = Asset::factory()->create();

        $this->getJson("/api/v1/assets/{$asset->id}")->assertOk()->assertJsonPath('data.id', $asset->id);
    }
}
