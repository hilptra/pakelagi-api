<?php

namespace App\Actions\Products;

use App\Models\ProductImage;
use App\Services\FrontendCache;
use Illuminate\Support\Facades\DB;

class SetPrimaryProductImage
{
    public function __construct(private readonly FrontendCache $frontend) {}

    public function execute(ProductImage $image): ProductImage
    {
        DB::transaction(function () use ($image) {
            ProductImage::where('product_id', $image->product_id)
                ->where('id', '!=', $image->id)
                ->update(['is_primary' => false]);

            $image->update(['is_primary' => true]);
        });

        $this->frontend->product($image->product);

        return $image;
    }
}
