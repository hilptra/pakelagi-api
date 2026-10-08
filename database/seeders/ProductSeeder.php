<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $disk = Storage::disk(config('pakelagi.images.disk', 'public'));

        // Minimal 1x1 WebP placeholder file
        $demoBytes = base64_decode('UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAQAcJaQAA3AA/v3AgAA=');

        if (! $disk->exists('products/demo.webp')) {
            $disk->put('products/demo.webp', $demoBytes);
            $disk->put('products/demo_thumb.webp', $demoBytes);
        }

        Category::all()->each(function (Category $category) {
            $products = collect([])
                ->concat(Product::factory()->count(6)->forCategory($category)->create())
                ->concat(Product::factory()->count(2)->forCategory($category)->sold()->create())
                ->concat(Product::factory()->count(1)->forCategory($category)->hidden()->create());

            foreach ($products as $product) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => 'products/demo.webp',
                    'thumbnail_path' => 'products/demo_thumb.webp',
                    'sort_order' => 1,
                    'is_primary' => true,
                ]);
            }
        });
    }
}
