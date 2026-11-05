<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VendorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_vendors_ordered_by_name(): void
    {
        foreach (['Contoso', 'Fabrikam', 'Northwind'] as $name) {
            Vendor::factory()->create(['name' => $name]);
        }

        $response = $this->getJson('/api/v1/vendors')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'contact_person', 'email', 'phone']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.*.name', ['Contoso', 'Fabrikam', 'Northwind'])
            ->assertJsonPath('meta.total', 3);

        $this->assertSame(
            ['id', 'name', 'contact_person', 'email', 'phone'],
            array_keys($response->json('data.0')),
        );
    }

    public function test_index_is_paginated(): void
    {
        Vendor::factory()->count(3)->create();

        $this->getJson('/api/v1/vendors?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_index_rejects_invalid_pagination(): void
    {
        $this->getJson('/api/v1/vendors?per_page=abc')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonValidationErrors('per_page', 'error.errors');
    }

    public function test_only_listing_is_exposed(): void
    {
        $vendor = Vendor::factory()->create();

        $this->getJson("/api/v1/vendors/{$vendor->id}")->assertNotFound();
        $this->postJson('/api/v1/vendors', ['name' => 'X'])->assertMethodNotAllowed();
    }
}
