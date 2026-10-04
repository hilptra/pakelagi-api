<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_hidden_products_are_excluded_but_sold_ones_are_kept(): void
    {
        Product::factory()->create(['slug' => 'tersedia']);
        Product::factory()->sold()->create(['slug' => 'terjual']);
        Product::factory()->hidden()->create(['slug' => 'tersembunyi']);

        $response = $this->getJson('/api/v1/products')->assertOk();

        $response->assertJsonCount(2, 'data');
        $slugs = collect($response->json('data'))->pluck('slug');
        $this->assertTrue($slugs->contains('tersedia'));
        $this->assertTrue($slugs->contains('terjual'));
        $this->assertFalse($slugs->contains('tersembunyi'));
    }

    public function test_sold_products_are_listed_after_available_ones(): void
    {
        Product::factory()->sold()->create(['slug' => 'terjual', 'created_at' => now()]);
        Product::factory()->create(['slug' => 'tersedia', 'created_at' => now()->subDay()]);

        $this->getJson('/api/v1/products')
            ->assertJsonPath('data.0.slug', 'tersedia')
            ->assertJsonPath('data.1.slug', 'terjual');
    }

    public function test_it_filters_by_category_slug(): void
    {
        $atasan = Category::factory()->create(['slug' => 'atasan']);
        $bawahan = Category::factory()->create(['slug' => 'bawahan']);
        Product::factory()->create(['category_id' => $atasan->id]);
        Product::factory()->count(2)->create(['category_id' => $bawahan->id]);

        $this->getJson('/api/v1/products?category=bawahan')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_filters_by_size(): void
    {
        Product::factory()->create(['size_label' => 'M']);
        Product::factory()->create(['size_label' => 'L']);

        $this->getJson('/api/v1/products?size=L')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.size_label', 'L');
    }

    public function test_it_filters_by_price_range(): void
    {
        Product::factory()->create(['price' => 50000]);
        Product::factory()->create(['price' => 100000]);
        Product::factory()->create(['price' => 200000]);

        $this->getJson('/api/v1/products?min_price=60000&max_price=150000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price', 100000);
    }

    public function test_it_searches_by_keyword(): void
    {
        Product::factory()->create(['name' => 'Kemeja Flanel Merah', 'brand' => null, 'description' => 'Bahan tebal']);
        Product::factory()->create(['name' => 'Celana Chino', 'brand' => null, 'description' => 'Bahan ringan']);

        $this->getJson('/api/v1/products?q=flanel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Kemeja Flanel Merah');
    }

    public function test_it_sorts_by_price_ascending(): void
    {
        Product::factory()->create(['slug' => 'mahal', 'price' => 300000]);
        Product::factory()->create(['slug' => 'murah', 'price' => 30000]);

        $this->getJson('/api/v1/products?sort=price_asc')
            ->assertJsonPath('data.0.slug', 'murah')
            ->assertJsonPath('data.1.slug', 'mahal');
    }

    public function test_it_fetches_products_by_slugs_and_ignores_unknown_ones(): void
    {
        Product::factory()->create(['slug' => 'a']);
        Product::factory()->create(['slug' => 'b']);
        Product::factory()->create(['slug' => 'c']);

        $this->getJson('/api/v1/products?slugs=a,b,tidak-ada')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_paginates_results(): void
    {
        Product::factory()->count(3)->create();

        $this->getJson('/api/v1/products?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_it_rejects_invalid_parameters(): void
    {
        $this->getJson('/api/v1/products?per_page=100')->assertStatus(422)->assertJsonValidationErrors('per_page');
        $this->getJson('/api/v1/products?sort=acak')->assertStatus(422)->assertJsonValidationErrors('sort');
        $this->getJson('/api/v1/products?min_price=100&max_price=50')->assertStatus(422)->assertJsonValidationErrors('max_price');
    }
}