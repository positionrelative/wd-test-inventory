<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\Contract;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContractApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_does_not_include_assets(): void
    {
        Contract::factory()->count(2)->hasAttached(Asset::factory()->count(2), relationship: 'assets')->create();

        $response = $this->getJson('/api/v1/contracts')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'contract_number', 'start_date', 'end_date', 'vendor']]]);

        foreach ($response->json('data') as $contract) {
            $this->assertArrayNotHasKey('assets', $contract);
        }
    }

    public function test_show_includes_assets(): void
    {
        $contract = Contract::factory()->create();
        $assets = Asset::factory()->count(2)->create();
        $contract->assets()->attach($assets);

        $this->getJson("/api/v1/contracts/{$contract->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $contract->id)
            ->assertJsonPath('data.start_date', $contract->start_date->toDateString())
            ->assertJsonPath('data.vendor.id', $contract->vendor_id)
            ->assertJsonCount(2, 'data.assets')
            ->assertJsonStructure(['data' => ['assets' => [['id', 'asset_tag', 'model', 'attached_at']]]]);
    }

    public function test_store_creates_contract(): void
    {
        $vendor = Vendor::factory()->create();

        $this->postJson('/api/v1/contracts', [
            'title' => 'Laptop lease',
            'contract_number' => 'CN-1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'vendor_id' => $vendor->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.contract_number', 'CN-1')
            ->assertJsonPath('data.end_date', '2026-12-31')
            ->assertJsonPath('data.assets', []);

        $this->assertDatabaseHas('contracts', ['contract_number' => 'CN-1', 'vendor_id' => $vendor->id]);
    }

    public function test_store_validates_dates_and_uniqueness(): void
    {
        $existing = Contract::factory()->create();

        $this->postJson('/api/v1/contracts', [
            'title' => 'Duplicate',
            'contract_number' => $existing->contract_number,
            'start_date' => '2026-12-31',
            'end_date' => '2026-01-01',
            'vendor_id' => $existing->vendor_id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['contract_number', 'end_date'], 'error.errors');
    }

    public function test_same_contract_number_is_allowed_for_another_vendor(): void
    {
        $existing = Contract::factory()->create();

        $this->postJson('/api/v1/contracts', [
            'title' => 'Other vendor',
            'contract_number' => $existing->contract_number,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'vendor_id' => Vendor::factory()->create()->id,
        ])->assertCreated();
    }

    public function test_partial_update_checks_dates_against_stored_values(): void
    {
        $contract = Contract::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->patchJson("/api/v1/contracts/{$contract->id}", ['end_date' => '2025-06-30'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('end_date', 'error.errors');

        $this->patchJson("/api/v1/contracts/{$contract->id}", ['start_date' => '2027-01-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_date', 'error.errors');

        $this->patchJson("/api/v1/contracts/{$contract->id}", ['title' => 'Renamed', 'end_date' => '2027-06-30'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Renamed')
            ->assertJsonPath('data.end_date', '2027-06-30');
    }

    public function test_destroy_deletes_contract_without_assets(): void
    {
        $contract = Contract::factory()->create();

        $this->deleteJson("/api/v1/contracts/{$contract->id}")->assertNoContent();

        $this->assertModelMissing($contract);
    }

    public function test_destroy_refuses_contract_with_assets(): void
    {
        $contract = Contract::factory()->hasAttached(Asset::factory(), relationship: 'assets')->create();

        $this->deleteJson("/api/v1/contracts/{$contract->id}")
            ->assertConflict()
            ->assertJsonPath('error.code', 'contract_has_assets');

        $this->assertModelExists($contract);
    }
}
