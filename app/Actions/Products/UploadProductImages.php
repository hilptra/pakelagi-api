<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class UploadProductImages
{
    public function __construct(private readonly ProductImageProcessor $processor) {}

    /**
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, ProductImage>
     */
    public function execute(Product $product, array $files): Collection
    {
        $limit = (int) config('pakelagi.images.max_per_product');
        $existing = ProductImage::where('product_id', $product->id)->count();

        if ($existing + count($files) > $limit) {
            throw ValidationException::withMessages([
                'images' => ["Satu produk maksimal {$limit} foto (saat ini sudah ada {$existing})."],
            ]);
        }

        $stored = [];

        try {
            foreach ($files as $file) {
                $stored[] = $this->processor->store($file, $product->id);
            }

            return DB::transaction(fn () => $this->createRecords($product, $stored, $existing));
        } catch (Throwable $e) {
            $this->processor->delete(collect($stored)->flatten()->all());

            throw $e;
        }
    }

    /**
     * @param  array<int, array{path: string, thumbnail_path: string}>  $stored
     * @return Collection<int, ProductImage>
     */
    private function createRecords(Product $product, array $stored, int $existing): Collection
    {
        $nextOrder = $existing === 0
            ? 0
            : ((int) ProductImage::where('product_id', $product->id)->max('sort_order')) + 1;

        $needsPrimary = ! ProductImage::where('product_id', $product->id)
            ->where('is_primary', true)
            ->exists();

        $created = collect();

        foreach ($stored as $paths) {
            $created->push(ProductImage::create([
                'product_id' => $product->id,
                ...$paths,
                'sort_order' => $nextOrder++,
                'is_primary' => $needsPrimary,
            ]));

            $needsPrimary = false;
        }

        return $created;
    }
}
