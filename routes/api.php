<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;

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
        Route::apiResource('products', AdminProductController::class);
        Route::post('products/{product}/sold', [AdminProductController::class, 'markSold']);
        Route::patch('products/{product}/status', [AdminProductController::class, 'changeStatus']);
    });
});