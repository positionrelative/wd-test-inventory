<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_minimal_rows_with_compact_pagination(): void
    {
        Asset::factory()->count(3)->create();

        $this->getJson('/api/v1/assets?per_page=2')
            ->assertOk()
            ->assertExactJsonStructure([
                'data' => ['*' => ['id', 'asset_tag', 'serial_number']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_index_includes_model_and_manufacturer_as_sibling_objects(): void
    {
        $asset = Asset::factory()->create();
        $model = $asset->assetModel;

        $this->getJson('/api/v1/assets?include=model,manufacturer')
            ->assertOk()
            ->assertExactJsonStructure([
                'data' => ['*' => [
                    'id',
                    'asset_tag',
                    'serial_number',
                    'model' => ['id', 'name', 'model_number'],
                    'manufacturer' => ['id', 'name'],
                ]],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonPath('data.0.model', [
                'id' => $model->id,
                'name' => $model->name,
                'model_number' => $model->model_number,
            ])
            ->assertJsonPath('data.0.manufacturer', [
                'id' => $model->manufacturer->id,
                'name' => $model->manufacturer->name,
            ]);
    }

    public function test_index_includes_only_what_was_requested(): void
    {
        Asset::factory()->create();

        $this->getJson('/api/v1/assets?include=manufacturer')
            ->assertOk()
            ->assertExactJsonStructure([
                'data' => ['*' => ['id', 'asset_tag', 'serial_number', 'manufacturer' => ['id', 'name']]],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_index_eager_loads_includes_without_n_plus_one(): void
    {
        Asset::factory()->count(10)->create();

        DB::enableQueryLog();
        $this->getJson('/api/v1/assets?include=model,manufacturer')->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // count + assets + asset_models + manufacturers (+ traffic log insert), regardless of row count
        $this->assertLessThanOrEqual(5, $queries);
    }

    public function test_index_rejects_unknown_include(): void
    {
        $this->getJson('/api/v1/assets?include=model,owner')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.errors.include.0', 'Unknown include: owner. Allowed: model, manufacturer.');
    }

    public function test_index_rejects_invalid_per_page(): void
    {
        $this->getJson('/api/v1/assets?per_page=1000')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonValidationErrors('per_page', 'error.errors');
    }

    public function test_show_returns_details_relations_and_timestamps(): void
    {
        $asset = Asset::factory()->create();
        $contract = Contract::factory()->create();
        $contract->assets()->attach($asset);

        $this->getJson("/api/v1/assets/{$asset->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'asset_tag',
                    'serial_number',
                    'model' => ['id', 'name', 'model_number'],
                    'manufacturer' => ['id', 'name'],
                    'contracts' => [['id', 'title', 'contract_number', 'start_date', 'end_date']],
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonMissingPath('data.model.manufacturer')
            ->assertJsonPath('data.id', $asset->id)
            ->assertJsonPath('data.model.id', $asset->model_id)
            ->assertJsonPath('data.manufacturer.name', $asset->assetModel->manufacturer->name)
            ->assertJsonPath('data.contracts.0', [
                'id' => $contract->id,
                'title' => $contract->title,
                'contract_number' => $contract->contract_number,
                'start_date' => $contract->start_date->toDateString(),
                'end_date' => $contract->end_date->toDateString(),
            ]);
    }

    public function test_show_unknown_asset_returns_404(): void
    {
        $this->getJson('/api/v1/assets/999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found')
            ->assertJsonPath('error.message', 'Asset not found.');
    }

    public function test_store_creates_asset(): void
    {
        $model = AssetModel::factory()->create();

        $this->postJson('/api/v1/assets', [
            'asset_tag' => 'AT-000001',
            'serial_number' => 'SN-1',
            'model_id' => $model->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.asset_tag', 'AT-000001')
            ->assertJsonPath('data.model.id', $model->id);

        $this->assertDatabaseHas('assets', ['asset_tag' => 'AT-000001', 'model_id' => $model->id]);
    }

    public function test_store_validates_input(): void
    {
        $existing = Asset::factory()->create();

        $this->postJson('/api/v1/assets', [
            'asset_tag' => $existing->asset_tag,
            'model_id' => 999,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.status', 422)
            ->assertJsonValidationErrors(['asset_tag', 'model_id'], 'error.errors');
    }

    public function test_update_changes_only_given_fields(): void
    {
        $asset = Asset::factory()->create();

        $this->patchJson("/api/v1/assets/{$asset->id}", ['serial_number' => 'SN-NEW'])
            ->assertOk()
            ->assertJsonPath('data.serial_number', 'SN-NEW')
            ->assertJsonPath('data.asset_tag', $asset->asset_tag);
    }

    public function test_update_keeps_own_unique_values_valid(): void
    {
        $asset = Asset::factory()->create();

        $this->putJson("/api/v1/assets/{$asset->id}", [
            'asset_tag' => $asset->asset_tag,
            'serial_number' => $asset->serial_number,
            'model_id' => $asset->model_id,
        ])->assertOk();
    }

    public function test_destroy_deletes_asset(): void
    {
        $asset = Asset::factory()->create();

        $this->deleteJson("/api/v1/assets/{$asset->id}")->assertNoContent();

        $this->assertModelMissing($asset);
    }

    public function test_destroy_refuses_asset_on_a_contract(): void
    {
        $asset = Asset::factory()->create();
        Contract::factory()->create()->assets()->attach($asset);

        $this->deleteJson("/api/v1/assets/{$asset->id}")
            ->assertConflict()
            ->assertJsonPath('error.code', 'asset_in_use');

        $this->assertModelExists($asset);
    }
}
