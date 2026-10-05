<?php

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Support\UniqueSlug;

class CreateProduct
{
    public function execute(array $data): Product
    {
        return Product::create([
            ...$data,
            'slug' => UniqueSlug::make(Product::class, $data['name'], 'produk'),
            'status' => $data['status'] ?? ProductStatus::Hidden->value,
            'measurements' => empty($data['measurements']) ? null : $data['measurements'],
        ]);
    }
}