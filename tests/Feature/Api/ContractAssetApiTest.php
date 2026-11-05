<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContractAssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_contract_assets_paginated(): void
    {
        $contract = Contract::factory()->create();
        $contract->assets()->attach(Asset::factory()->count(3)->create());
        Asset::factory()->create(); // not on the contract

        $this->getJson("/api/v1/contracts/{$contract->id}/assets?per_page=2")
            ->assertOk()
            ->assertExactJsonStructure([
                'data' => ['*' => ['id', 'asset_tag', 'serial_number']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_index_supports_includes(): void
    {
        $contract = Contract::factory()->create();
        $asset = Asset::factory()->create();
        $contract->assets()->attach($asset);

        $this->getJson("/api/v1/contracts/{$contract->id}/assets?include=model,manufacturer")
            ->assertOk()
            ->assertJsonPath('data.0.model.id', $asset->model_id)
            ->assertJsonPath('data.0.manufacturer.id', $asset->assetModel->manufacturer_id);

        $this->getJson("/api/v1/contracts/{$contract->id}/assets?include=owner")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('include', 'error.errors');
    }

    public function test_index_unknown_contract_returns_404(): void
    {
        $this->getJson('/api/v1/contracts/999/assets')
            ->assertNotFound()
            ->assertJsonPath('error.message', 'Contract not found.');
    }

    public function test_attach_adds_asset(): void
    {
        $contract = Contract::factory()->create();
        $asset = Asset::factory()->create();

        $this->putJson("/api/v1/contracts/{$contract->id}/assets/{$asset->id}")->assertNoContent();

        $this->assertDatabaseHas('asset_contract', ['contract_id' => $contract->id, 'asset_id' => $asset->id]);
    }

    public function test_attach_is_idempotent(): void
    {
        $contract = Contract::factory()->create();
        $asset = Asset::factory()->create();

        $this->putJson("/api/v1/contracts/{$contract->id}/assets/{$asset->id}")->assertNoContent();
        $this->putJson("/api/v1/contracts/{$contract->id}/assets/{$asset->id}")->assertNoContent();

        $this->assertDatabaseCount('asset_contract', 1);
    }

    public function test_attach_unknown_asset_returns_404(): void
    {
        $contract = Contract::factory()->create();

        $this->putJson("/api/v1/contracts/{$contract->id}/assets/999")
            ->assertNotFound()
            ->assertJsonPath('error.message', 'Asset not found.');
    }

    public function test_detach_removes_asset(): void
    {
        $contract = Contract::factory()->create();
        $asset = Asset::factory()->create();
        $contract->assets()->attach($asset);

        $this->deleteJson("/api/v1/contracts/{$contract->id}/assets/{$asset->id}")->assertNoContent();

        $this->assertDatabaseMissing('asset_contract', ['contract_id' => $contract->id, 'asset_id' => $asset->id]);
        $this->assertModelExists($asset);
    }

    public function test_detach_asset_not_on_contract_returns_404(): void
    {
        $contract = Contract::factory()->create();
        $asset = Asset::factory()->create();

        $this->deleteJson("/api/v1/contracts/{$contract->id}/assets/{$asset->id}")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'asset_not_attached');
    }
}
