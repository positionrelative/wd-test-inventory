<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_assets(): void
    {
        Asset::factory()->count(3)->create();

        $this->getJson('/api/v1/assets?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'asset_tag',
                    'serial_number',
                    'model' => ['id', 'name', 'model_number', 'manufacturer' => ['id', 'name']],
                    'created_at',
                    'updated_at',
                ]],
                'links',
                'meta',
            ]);
    }

    public function test_index_rejects_invalid_per_page(): void
    {
        $this->getJson('/api/v1/assets?per_page=1000')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonValidationErrors('per_page', 'error.errors');
    }

    public function test_show_returns_asset(): void
    {
        $asset = Asset::factory()->create();

        $this->getJson("/api/v1/assets/{$asset->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $asset->id)
            ->assertJsonPath('data.asset_tag', $asset->asset_tag)
            ->assertJsonPath('data.model.id', $asset->model_id)
            ->assertJsonPath('data.model.manufacturer.id', $asset->assetModel->manufacturer_id)
            ->assertJsonPath('data.model.manufacturer.name', $asset->assetModel->manufacturer->name);
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
