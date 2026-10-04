<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Products\DeleteProductImage;
use App\Actions\Products\ReorderProductImages;
use App\Actions\Products\SetPrimaryProductImage;
use App\Actions\Products\UploadProductImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderProductImagesRequest;
use App\Http\Requests\UpdateProductImageRequest;
use App\Http\Requests\UploadProductImagesRequest;
use App\Http\Resources\AdminProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductImageController extends Controller
{
    public function store(
        UploadProductImagesRequest $request,
        Product $product,
        UploadProductImages $uploadProductImages,
    ): JsonResponse {
        $images = $uploadProductImages->execute($product, $request->file('images'));

        return AdminProductImageResource::collection($images)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    // $product tidak dipakai, tapi harus ada: urutan parameter harus sama dengan urutan di route.
    public function update(
        UpdateProductImageRequest $request,
        Product $product,
        ProductImage $image,
        SetPrimaryProductImage $setPrimaryProductImage,
    ): AdminProductImageResource {
        return new AdminProductImageResource($setPrimaryProductImage->execute($image));
    }

    public function reorder(
        ReorderProductImagesRequest $request,
        Product $product,
        ReorderProductImages $reorderProductImages,
    ): AnonymousResourceCollection {
        $reorderProductImages->execute($product, $request->validated('image_ids'));

        return AdminProductImageResource::collection($product->images()->get());
    }

    public function destroy(
        Product $product,
        ProductImage $image,
        DeleteProductImage $deleteProductImage,
    ): Response {
        $deleteProductImage->execute($image);

        return response()->noContent();
    }
}
