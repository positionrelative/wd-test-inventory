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

        $response = $this->getJson("/api/v1/contracts/{$contract->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $contract->id)
            ->assertJsonPath('data.start_date', $contract->start_date->toDateString())
            ->assertJsonPath('data.vendor.id', $contract->vendor_id)
            ->assertJsonMissingPath('data.vendor_id')
            ->assertJsonPath('data.assets_count', 2)
            ->assertJsonCount(2, 'data.assets');

        // Compact asset rows: no timestamps.
        $this->assertSame(
            ['id', 'asset_tag', 'serial_number', 'model', 'manufacturer', 'attached_at'],
            array_keys($response->json('data.assets.0')),
        );
    }

    public function test_show_embeds_only_the_first_assets_but_counts_all(): void
    {
        config(['api.contract_assets_preview' => 3]);
        $contract = Contract::factory()->create();
        $assets = Asset::factory()->count(5)->create();
        $contract->assets()->attach($assets);

        $this->getJson("/api/v1/contracts/{$contract->id}")
            ->assertOk()
            ->assertJsonPath('data.assets_count', 5)
            ->assertJsonPath('data.assets.*.id', $assets->take(3)->pluck('id')->all());
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
            ->assertJsonPath('data.assets_count', 0)
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

    public function test_changing_only_vendor_validates_contract_number_uniqueness(): void
    {
        $taken = Contract::factory()->create(['contract_number' => 'CN-SAME']);
        $contract = Contract::factory()->create(['contract_number' => 'CN-SAME']);

        $this->patchJson("/api/v1/contracts/{$contract->id}", ['vendor_id' => $taken->vendor_id])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.errors.vendor_id.0', 'The contract number has already been taken for this vendor.');
    }

    public function test_changing_only_contract_number_validates_uniqueness_within_vendor(): void
    {
        $taken = Contract::factory()->create(['contract_number' => 'CN-TAKEN']);
        $contract = Contract::factory()->create(['vendor_id' => $taken->vendor_id]);

        $this->patchJson("/api/v1/contracts/{$contract->id}", ['contract_number' => 'CN-TAKEN'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contract_number', 'error.errors');
    }

    public function test_update_keeps_own_contract_number_valid(): void
    {
        $contract = Contract::factory()->create();

        $this->patchJson("/api/v1/contracts/{$contract->id}", [
            'contract_number' => $contract->contract_number,
            'vendor_id' => $contract->vendor_id,
        ])->assertOk();
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
