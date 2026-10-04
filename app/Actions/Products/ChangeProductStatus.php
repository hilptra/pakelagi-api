<?php

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use InvalidArgumentException;

class ChangeProductStatus
{
    public function execute(Product $product, ProductStatus $target): Product
    {
        if ($target === ProductStatus::Sold) {
            throw new InvalidArgumentException('Gunakan MarkProductAsSold untuk menandai terjual.');
        }

        if ($product->status === $target) {
            return $product;
        }

        $product->update([
            'status' => $target,
            'sold_at' => $target === ProductStatus::Available ? null : $product->sold_at,
        ]);

        return $product;
    }
}