<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Manufacturer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManufacturerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_manufacturers_ordered_by_name(): void
    {
        foreach (['Lenovo', 'Apple', 'Dell'] as $name) {
            Manufacturer::factory()->create(['name' => $name]);
        }

        $response = $this->getJson('/api/v1/manufacturers')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.*.name', ['Apple', 'Dell', 'Lenovo'])
            ->assertJsonPath('meta.total', 3);

        $this->assertSame(['id', 'name'], array_keys($response->json('data.0')));
    }

    public function test_index_is_paginated(): void
    {
        Manufacturer::factory()->count(3)->create();

        $this->getJson('/api/v1/manufacturers?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_index_rejects_invalid_pagination(): void
    {
        $this->getJson('/api/v1/manufacturers?per_page=0')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonValidationErrors('per_page', 'error.errors');
    }

    public function test_only_listing_is_exposed(): void
    {
        $manufacturer = Manufacturer::factory()->create();

        $this->getJson("/api/v1/manufacturers/{$manufacturer->id}")->assertNotFound();
        $this->postJson('/api/v1/manufacturers', ['name' => 'X'])->assertMethodNotAllowed();
    }
}
