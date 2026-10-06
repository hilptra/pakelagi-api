<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Categories\CreateCategory;
use App\Actions\Categories\DeleteCategory;
use App\Actions\Categories\UpdateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCategoryRequest;
use App\Http\Resources\AdminCategoryResource;
use App\Models\Category;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Admin - Kategori')]
class CategoryController extends Controller
{
    /**
     * Daftar semua kategori (admin)
     */
    public function index(): AnonymousResourceCollection
    {
        return AdminCategoryResource::collection(
            Category::withCount('products')->orderBy('name')->get()
        );
    }

    /**
     * Buat kategori baru
     */
    public function store(SaveCategoryRequest $request, CreateCategory $createCategory): JsonResponse
    {
        $category = $createCategory->execute($request->validated());

        return (new AdminCategoryResource($category->loadCount('products')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Perbarui data kategori
     */
    public function update(
        SaveCategoryRequest $request,
        Category $category,
        UpdateCategory $updateCategory,
    ): AdminCategoryResource {
        $category = $updateCategory->execute($category, $request->validated());

        return new AdminCategoryResource($category->loadCount('products'));
    }

    /**
     * Hapus kategori
     */
    public function destroy(Category $category, DeleteCategory $deleteCategory): Response
    {
        $deleteCategory->execute($category);

        return response()->noContent();
    }
}
