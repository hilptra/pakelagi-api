<?php

namespace App\Actions\Products;

use App\Models\ProductImage;
use App\Services\FrontendCache;
use App\Services\ProductImageProcessor;
use Illuminate\Support\Facades\DB;

class DeleteProductImage
{
    public function __construct(
        private readonly ProductImageProcessor $processor,
        private readonly FrontendCache $frontend,
    ) {}

    public function execute(ProductImage $image): void
    {
        $product = $image->product;
        $wasPrimary = $image->is_primary;
        $paths = [$image->path, $image->thumbnail_path];

        DB::transaction(function () use ($image, $product, $wasPrimary) {
            $image->delete();

            if ($wasPrimary) {
                ProductImage::where('product_id', $product->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first()
                    ?->update(['is_primary' => true]);
            }
        });

        $this->processor->delete($paths);
        $this->frontend->product($product);
    }
}