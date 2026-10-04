<?php

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class MarkProductAsSold
{
    public function execute(Product $product): Product
    {
        if ($product->status === ProductStatus::Sold) {
            return $product;
        }

        if ($product->status === ProductStatus::Hidden) {
            throw ValidationException::withMessages([
                'status' => ['Produk tersembunyi harus ditayangkan dulu sebelum ditandai terjual.'],
            ]);
        }

        $product->update([
            'status' => ProductStatus::Sold,
            'sold_at' => now(),
        ]);

        return $product;
    }
}
