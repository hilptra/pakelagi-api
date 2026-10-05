<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Services\FrontendCache;

class UpdateProduct
{
    public function __construct(private readonly FrontendCache $frontend) {}

    public function execute(Product $product, array $data): Product
    {
        $data['measurements'] = empty($data['measurements']) ? null : $data['measurements'];

        $product->update($data);

        $this->frontend->product($product);

        return $product;
    }
}