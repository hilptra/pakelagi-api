<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_categories_with_measurement_fields(): void
    {
        Category::factory()->create([
            'name' => 'Atasan',
            'slug' => 'atasan',
            'measurement_fields' => ['lebar_dada', 'panjang_baju'],
        ]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'atasan')
            ->assertJsonPath('data.0.measurement_fields', ['lebar_dada', 'panjang_baju']);
    }
}