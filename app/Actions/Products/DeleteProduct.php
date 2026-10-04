<?php

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class DeleteProduct
{
    public function execute(Product $product): void
    {
        $paths = $product->images()->pluck('path')->all();

        $product->delete();

        Storage::disk('public')->delete($paths);
    }
}