<?php

namespace App\Actions\Products;

use App\Models\Product;

class UpdateProduct
{
    public function execute(Product $product, array $data): Product
    {
        $data['measurements'] = empty($data['measurements']) ? null : $data['measurements'];

        $product->update($data);

        return $product;
    }
}
