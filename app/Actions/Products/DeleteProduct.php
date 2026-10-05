<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\FrontendCache;
use App\Services\ProductImageProcessor;

class DeleteProduct
{
    public function __construct(
        private readonly ProductImageProcessor $processor,
        private readonly FrontendCache $frontend,
    ) {}

    public function execute(Product $product): void
    {
        $paths = $product->images->flatMap(
            fn (ProductImage $image) => [$image->path, $image->thumbnail_path]
        );

        $product->delete();

        $this->processor->delete($paths);
        $this->frontend->product($product);
    }
}