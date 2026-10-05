<?php

use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\ProductImageController as AdminProductImageController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Publik
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{slug}', [ProductController::class, 'show']);
    });

    // Autentikasi admin
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Admin (wajib login)
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        // Products
        Route::apiResource('products', AdminProductController::class);
        Route::post('products/{product}/sold', [AdminProductController::class, 'markSold']);
        Route::patch('products/{product}/status', [AdminProductController::class, 'changeStatus']);

        // Product Images
        Route::scopeBindings()->group(function () {
            Route::post('products/{product}/images', [AdminProductImageController::class, 'store']);
            Route::put('products/{product}/images/order', [AdminProductImageController::class, 'reorder']);
            Route::patch('products/{product}/images/{image}', [AdminProductImageController::class, 'update']);
            Route::delete('products/{product}/images/{image}', [AdminProductImageController::class, 'destroy']);
        });

        // Categories
        Route::apiResource('categories', AdminCategoryController::class)->except('show');
    });
});
