<?php

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Support\Str;

class CreateProduct
{
    public function execute(array $data): Product
    {
        return Product::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
            'status' => $data['status'] ?? ProductStatus::Hidden->value,
            'measurements' => empty($data['measurements']) ? null : $data['measurements'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'produk';
        $slug = $base;
        $suffix = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}