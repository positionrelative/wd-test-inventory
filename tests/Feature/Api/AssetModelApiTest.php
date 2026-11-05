<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Manufacturer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssetModelApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_models_by_name_with_manufacturer(): void
    {
        foreach (['ThinkPad X1', 'EliteBook 840', 'Latitude 7440'] as $name) {
            AssetModel::factory()->create(['name' => $name]);
        }

        $first = $this->getJson('/api/v1/asset-models?per_page=2')
            ->assertOk()
            ->assertExactJsonStructure([
                'data' => ['*' => ['id', 'name', 'model_number', 'manufacturer' => ['id', 'name']]],
                'meta' => ['per_page', 'next_cursor', 'prev_cursor'],
            ])
            ->assertJsonPath('data.*.name', ['EliteBook 840', 'Latitude 7440']);

        $this->getJson('/api/v1/asset-models?per_page=2&cursor=' . $first->json('meta.next_cursor'))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['ThinkPad X1'])
            ->assertJsonPath('meta.next_cursor', null);
    }

    public function test_count(): void
    {
        AssetModel::factory()->count(2)->create();

        $this->getJson('/api/v1/asset-models/count')->assertExactJson(['data' => ['count' => 2]]);
    }

    public function test_show_returns_model_with_manufacturer(): void
    {
        $model = AssetModel::factory()->create();

        $this->getJson("/api/v1/asset-models/{$model->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $model->id)
            ->assertJsonPath('data.manufacturer.id', $model->manufacturer_id);

        $this->getJson('/api/v1/asset-models/999')
            ->assertNotFound()
            ->assertJsonPath('error.message', 'Asset model not found.');
    }

    public function test_store_creates_model(): void
    {
        $manufacturer = Manufacturer::factory()->create();

        $this->postJson('/api/v1/asset-models', [
            'name' => 'ThinkPad X1 Carbon',
            'model_number' => '21HM',
            'manufacturer_id' => $manufacturer->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.model_number', '21HM')
            ->assertJsonPath('data.manufacturer.id', $manufacturer->id);

        $this->getJson('/api/v1/asset-models/count')->assertExactJson(['data' => ['count' => 1]]);
    }

    public function test_store_validates_input(): void
    {
        $this->postJson('/api/v1/asset-models', ['manufacturer_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'model_number', 'manufacturer_id'], 'error.errors');
    }

    public function test_model_number_is_unique_per_manufacturer(): void
    {
        $taken = AssetModel::factory()->create(['model_number' => 'MN-1']);

        $this->postJson('/api/v1/asset-models', [
            'name' => 'Duplicate',
            'model_number' => 'MN-1',
            'manufacturer_id' => $taken->manufacturer_id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.errors.model_number.0', 'The model number has already been taken for this manufacturer.');

        $this->postJson('/api/v1/asset-models', [
            'name' => 'Same number, other maker',
            'model_number' => 'MN-1',
            'manufacturer_id' => Manufacturer::factory()->create()->id,
        ])->assertCreated();
    }

    public function test_changing_only_manufacturer_validates_uniqueness(): void
    {
        $taken = AssetModel::factory()->create(['model_number' => 'MN-1']);
        $model = AssetModel::factory()->create(['model_number' => 'MN-1']);

        $this->patchJson("/api/v1/asset-models/{$model->id}", ['manufacturer_id' => $taken->manufacturer_id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('manufacturer_id', 'error.errors');
    }

    public function test_update_changes_only_given_fields(): void
    {
        $model = AssetModel::factory()->create();

        $this->patchJson("/api/v1/asset-models/{$model->id}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed')
            ->assertJsonPath('data.model_number', $model->model_number);
    }

    public function test_destroy_deletes_unused_model(): void
    {
        $model = AssetModel::factory()->create();

        $this->deleteJson("/api/v1/asset-models/{$model->id}")->assertNoContent();

        $this->assertModelMissing($model);
    }

    public function test_destroy_refuses_model_used_by_assets(): void
    {
        $asset = Asset::factory()->create();

        $this->deleteJson("/api/v1/asset-models/{$asset->model_id}")
            ->assertConflict()
            ->assertJsonPath('error.code', 'asset_model_in_use');

        $this->assertModelExists($asset->assetModel);
    }

    public function test_nested_model_in_assets_has_no_manufacturer(): void
    {
        $asset = Asset::factory()->create();

        $this->getJson("/api/v1/assets/{$asset->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.model.manufacturer')
            ->assertJsonPath('data.manufacturer.id', $asset->assetModel->manufacturer_id);
    }
}
