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

        $this->getJson('/api/v1/vendors')
            ->assertOk()
            ->assertExactJsonStructure([
                'data' => ['*' => ['id', 'name', 'contact_person', 'email', 'phone']],
                'meta' => ['per_page', 'next_cursor', 'prev_cursor'],
            ])
            ->assertJsonPath('data.*.name', ['Contoso', 'Fabrikam', 'Northwind'])
            ->assertJsonPath('meta.next_cursor', null);
    }

    public function test_index_is_cursor_paginated(): void
    {
        Vendor::factory()->count(3)->create();

        $first = $this->getJson('/api/v1/vendors?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/vendors?per_page=2&cursor=' . $first->json('meta.next_cursor'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.next_cursor', null);
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
