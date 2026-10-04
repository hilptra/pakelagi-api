<?php

namespace Tests\Feature\Api\V1;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_an_available_product_with_details(): void
    {
        Product::factory()->create(['slug' => 'kemeja-flanel']);

        $this->getJson('/api/v1/products/kemeja-flanel')
            ->assertOk()
            ->assertJsonPath('data.slug', 'kemeja-flanel')
            ->assertJsonStructure(['data' => [
                'slug', 'name', 'description', 'price', 'condition', 'status',
                'measurements', 'category' => ['name', 'slug'], 'images',
            ]]);
    }

    public function test_sold_products_remain_accessible_by_slug(): void
    {
        Product::factory()->sold()->create(['slug' => 'sudah-laku']);

        $this->getJson('/api/v1/products/sudah-laku')
            ->assertOk()
            ->assertJsonPath('data.status', 'sold');
    }

    public function test_hidden_products_return_not_found(): void
    {
        Product::factory()->hidden()->create(['slug' => 'rahasia']);

        $this->getJson('/api/v1/products/rahasia')->assertNotFound();
    }

    public function test_unknown_slug_returns_not_found(): void
    {
        $this->getJson('/api/v1/products/tidak-ada')->assertNotFound();
    }

    public function test_not_found_is_json_even_without_accept_header(): void
    {
        $this->get('/api/v1/products/tidak-ada')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');
    }
}