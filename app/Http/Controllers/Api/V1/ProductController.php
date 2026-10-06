<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListProductsRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Produk')]
class ProductController extends Controller
{
    private const DEFAULT_PER_PAGE = 12;

    /**
     * Daftar produk publik (katalog)
     */
    public function index(ListProductsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->filters();

        $products = Product::query()
            ->visible()
            ->filter($filters)
            ->soldLast()
            ->sortedBy($filters['sort'] ?? null)
            ->with(['category', 'primaryImage'])
            ->paginate((int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE))
            ->withQueryString();

        return ProductListResource::collection($products);
    }

    /**
     * Detail produk publik berdasarkan slug
     */
    public function show(string $slug): ProductResource
    {
        $product = Product::query()
            ->visible()
            ->with(['category', 'images'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProductResource($product);
    }
}
