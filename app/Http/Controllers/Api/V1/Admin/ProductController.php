<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Products\ChangeProductStatus;
use App\Actions\Products\CreateProduct;
use App\Actions\Products\DeleteProduct;
use App\Actions\Products\MarkProductAsSold;
use App\Actions\Products\UpdateProduct;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListAdminProductsRequest;
use App\Http\Requests\SaveProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductStatusRequest;
use App\Http\Resources\AdminProductListResource;
use App\Http\Resources\AdminProductResource;
use App\Models\Product;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Admin - Produk')]
class ProductController extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    /**
     * Daftar semua produk (admin)
     */
    public function index(ListAdminProductsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->filters();

        $products = Product::query()
            ->filter($filters)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->sortedBy($filters['sort'] ?? null)
            ->with(['category', 'primaryImage'])
            ->paginate((int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE))
            ->withQueryString();

        return AdminProductListResource::collection($products);
    }

    /**
     * Buat produk baru
     */
    public function store(StoreProductRequest $request, CreateProduct $createProduct): JsonResponse
    {
        $product = $createProduct->execute($request->validated());

        return (new AdminProductResource($product->load(['category', 'images'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Detail produk (admin)
     */
    public function show(Product $product): AdminProductResource
    {
        return new AdminProductResource($product->load(['category', 'images']));
    }

    /**
     * Perbarui data produk
     */
    public function update(SaveProductRequest $request, Product $product, UpdateProduct $updateProduct): AdminProductResource
    {
        $product = $updateProduct->execute($product, $request->validated());

        return new AdminProductResource($product->load(['category', 'images']));
    }

    /**
     * Hapus produk
     */
    public function destroy(Product $product, DeleteProduct $deleteProduct): Response
    {
        $deleteProduct->execute($product);

        return response()->noContent();
    }

    /**
     * Tandai produk sebagai terjual (sold)
     */
    public function markSold(Product $product, MarkProductAsSold $markProductAsSold): AdminProductResource
    {
        $product = $markProductAsSold->execute($product);

        return new AdminProductResource($product->load(['category', 'images']));
    }

    /**
     * Ubah status produk (available / hidden)
     */
    public function changeStatus(
        UpdateProductStatusRequest $request,
        Product $product,
        ChangeProductStatus $changeProductStatus,
    ): AdminProductResource {
        $product = $changeProductStatus->execute(
            $product,
            ProductStatus::from($request->validated('status')),
        );

        return new AdminProductResource($product->load(['category', 'images']));
    }
}
