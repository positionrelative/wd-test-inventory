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

        $this->getJson('/api/v1/manufacturers')
            ->assertOk()
            ->assertExactJsonStructure([
                'data' => ['*' => ['id', 'name']],
                'meta' => ['per_page', 'next_cursor', 'prev_cursor'],
            ])
            ->assertJsonPath('data.*.name', ['Apple', 'Dell', 'Lenovo'])
            ->assertJsonPath('meta.next_cursor', null);
    }

    public function test_index_is_cursor_paginated_in_name_order(): void
    {
        foreach (['Lenovo', 'Apple', 'Dell'] as $name) {
            Manufacturer::factory()->create(['name' => $name]);
        }

        $first = $this->getJson('/api/v1/manufacturers?per_page=2')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Apple', 'Dell']);

        $this->getJson('/api/v1/manufacturers?per_page=2&cursor=' . $first->json('meta.next_cursor'))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Lenovo'])
            ->assertJsonPath('meta.next_cursor', null);
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
