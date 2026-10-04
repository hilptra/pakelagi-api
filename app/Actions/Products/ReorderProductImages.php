<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;

class ReorderProductImages
{
    /**
     * @param  array<int, int|string>  $imageIds  ID foto sesuai urutan yang diinginkan
     */
    public function execute(Product $product, array $imageIds): void
    {
        DB::transaction(function () use ($product, $imageIds) {
            foreach (array_values($imageIds) as $position => $id) {
                ProductImage::where('product_id', $product->id)
                    ->whereKey($id)
                    ->update(['sort_order' => $position]);
            }
        });
    }
}
