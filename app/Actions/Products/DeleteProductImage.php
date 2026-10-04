<?php

namespace App\Actions\Products;

use App\Models\ProductImage;
use App\Services\ProductImageProcessor;
use Illuminate\Support\Facades\DB;

class DeleteProductImage
{
    public function __construct(private readonly ProductImageProcessor $processor) {}

    public function execute(ProductImage $image): void
    {
        $productId = $image->product_id;
        $wasPrimary = $image->is_primary;
        $paths = [$image->path, $image->thumbnail_path];

        DB::transaction(function () use ($image, $productId, $wasPrimary) {
            $image->delete();

            if ($wasPrimary) {
                ProductImage::where('product_id', $productId)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first()
                    ?->update(['is_primary' => true]);
            }
        });

        $this->processor->delete($paths);
    }
}
