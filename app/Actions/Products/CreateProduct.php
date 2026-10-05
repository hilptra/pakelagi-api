<?php

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\FrontendCache;
use App\Support\UniqueSlug;

class CreateProduct
{
    public function __construct(private readonly FrontendCache $frontend) {}

    public function execute(array $data): Product
    {
        $product = Product::create([
            ...$data,
            'slug' => UniqueSlug::make(Product::class, $data['name'], 'produk'),
            'status' => $data['status'] ?? ProductStatus::Hidden->value,
            'measurements' => empty($data['measurements']) ? null : $data['measurements'],
        ]);

        $this->frontend->product($product);

        return $product;
    }
}