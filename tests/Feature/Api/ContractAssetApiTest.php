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

    public function test_attach_adds_asset_and_returns_contract_with_assets(): void
    {
        $contract = Contract::factory()->create();
        $asset = Asset::factory()->create();

        $this->putJson("/api/v1/contracts/{$contract->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $contract->id)
            ->assertJsonPath('data.assets.0.id', $asset->id);

        $this->assertDatabaseHas('asset_contract', ['contract_id' => $contract->id, 'asset_id' => $asset->id]);
    }

    public function test_attach_is_idempotent(): void
    {
        $contract = Contract::factory()->create();
        $asset = Asset::factory()->create();

        $this->putJson("/api/v1/contracts/{$contract->id}/assets/{$asset->id}")->assertOk();
        $this->putJson("/api/v1/contracts/{$contract->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.assets');

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
