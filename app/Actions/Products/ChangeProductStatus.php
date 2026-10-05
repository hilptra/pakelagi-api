<?php

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\FrontendCache;
use InvalidArgumentException;

class ChangeProductStatus
{
    public function __construct(private readonly FrontendCache $frontend) {}

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

        $this->frontend->product($product);

        return $product;
    }
}