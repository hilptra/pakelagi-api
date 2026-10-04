<?php

namespace Tests\Feature\Api\V1;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicProductImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_exposes_urls_but_not_internal_ids(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create(['slug' => 'baju']);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/1/a.webp',
            'thumbnail_path' => 'products/1/a_thumb.webp',
            'is_primary' => true,
        ]);

        $this->getJson('/api/v1/products/baju')
            ->assertOk()
            ->assertJsonStructure(['data' => ['images' => [['url', 'thumbnail_url', 'is_primary', 'sort_order']]]])
            ->assertJsonMissingPath('data.images.0.id');

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.primary_image.is_primary', true)
            ->assertJsonMissingPath('data.0.primary_image.id');
    }
}
