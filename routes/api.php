<?php

use App\Http\Controllers\Api\Admin\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Client\AuthController;
use App\Http\Controllers\Api\Client\HomeController;
use App\Http\Controllers\Api\Admin\CategoryController;

Route::prefix('auth')->group(function ($router) {

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:api_user')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

Route::prefix('home')->group(function ($router) {
    Route::get('/', [HomeController::class, 'index']);
});


Route::prefix('/admin')->group(function ($router) {
    Route::prefix('/products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/create', [ProductController::class, 'create']);
        Route::patch('/edit/{id}', [ProductController::class, 'update']);
        Route::get('/detail/{id}', [ProductController::class, 'detail']);
        Route::patch('/change-status/{id}', [ProductController::class, 'changeStatus']);
        Route::delete('/delete/{id}', [ProductController::class, 'destroy']);
    });

    Route::prefix('/categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/create', [CategoryController::class, 'create']);
        Route::patch('/edit/{id}', [CategoryController::class, 'update']);
        Route::patch('/change-status/{id}', [CategoryController::class, 'changeStatus']);
        Route::delete('/delete/{id}', [CategoryController::class, 'destroy']);
    });
});
