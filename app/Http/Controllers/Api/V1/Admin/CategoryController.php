<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Categories\CreateCategory;
use App\Actions\Categories\DeleteCategory;
use App\Actions\Categories\UpdateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCategoryRequest;
use App\Http\Resources\AdminCategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AdminCategoryResource::collection(
            Category::withCount('products')->orderBy('name')->get()
        );
    }

    public function store(SaveCategoryRequest $request, CreateCategory $createCategory): JsonResponse
    {
        $category = $createCategory->execute($request->validated());

        return (new AdminCategoryResource($category->loadCount('products')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        SaveCategoryRequest $request,
        Category $category,
        UpdateCategory $updateCategory,
    ): AdminCategoryResource {
        $category = $updateCategory->execute($category, $request->validated());

        return new AdminCategoryResource($category->loadCount('products'));
    }

    public function destroy(Category $category, DeleteCategory $deleteCategory): Response
    {
        $deleteCategory->execute($category);

        return response()->noContent();
    }
}
