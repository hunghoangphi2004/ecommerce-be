<?php

use App\Http\Controllers\Api\Admin\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Client\AuthController;
use App\Http\Controllers\Api\Client\HomeController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\AccountController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Client\OrderController;
use App\Http\Controllers\Api\Admin\BranchController;
use App\Http\Controllers\Api\Admin\PageController;
use App\Http\Controllers\Api\Client\PageController as PageClientController;

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
    Route::get('/get-category/{slug}', [HomeController::class, 'getCategory']);
    Route::get('/get-product/{slug}', [HomeController::class, 'getProduct']);
    Route::get('/get-all-category', [HomeController::class, 'getAllCategory']);
    Route::get('/get-all-branch', [HomeController::class, 'getAllBranch']);
    Route::get('/get-branch/{slug}', [HomeController::class, 'getBranch']);
});

Route::prefix('pages')->group(function ($router) {
    Route::get('/get-page/{slug}', [PageClientController::class, 'getPage']);
});


Route::post('/checkout', [OrderController::class, 'checkout']);


Route::prefix('/admin')->group(function ($router) {

    Route::prefix('/auth')->group(function () {
        Route::post('/login', [AccountController::class, 'login']);
    });

    Route::middleware('auth:api_account')->group(function () {
        Route::prefix('/auth')->group(function () {
            Route::post('/logout', [AccountController::class, 'logout']);
        });

        Route::prefix('/products')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->middleware('permission_id:' . config('permission.product.view'));
            Route::post('/create', [ProductController::class, 'create']);
            Route::patch('/edit/{id}', [ProductController::class, 'update']);
            Route::get('/detail/{id}', [ProductController::class, 'detail']);
            Route::patch('/change-status/{id}', [ProductController::class, 'changeStatus']);
            Route::delete('/delete/{id}', [ProductController::class, 'destroy']);
        });

        Route::prefix('/categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/detail/{id}', [CategoryController::class, 'detail']);
            Route::post('/create', [CategoryController::class, 'create']);
            Route::patch('/edit/{id}', [CategoryController::class, 'update']);
            Route::patch('/change-status/{id}', [CategoryController::class, 'changeStatus']);
            Route::delete('/delete/{id}', [CategoryController::class, 'destroy']);
        });

        Route::prefix('/branches')->group(function () {
            Route::get('/', [BranchController::class, 'index']);
            Route::get('/detail/{id}', [BranchController::class, 'detail']);
            Route::post('/create', [BranchController::class, 'create']);
            Route::patch('/edit/{id}', [BranchController::class, 'update']);
            Route::patch('/change-status/{id}', [BranchController::class, 'changeStatus']);
            Route::delete('/delete/{id}', [BranchController::class, 'destroy']);
        });

        Route::prefix('/pages')->group(function () {
            Route::get('/', [PageController::class, 'index']);
            Route::get('/detail/{id}', [PageController::class, 'detail']);
            Route::post('/create', [PageController::class, 'create']);
            Route::patch('/edit/{id}', [PageController::class, 'update']);
            // Route::patch('/change-status/{id}', [BranchController::class, 'changeStatus']);
            Route::delete('/delete/{id}', [PageController::class, 'destroy']);
        });

        Route::prefix('/accounts')->group(function () {
            Route::post('/create', [AccountController::class, 'create']);
        });

        Route::prefix('/roles')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::get('/detail/{id}', [RoleController::class, 'detail']);
            Route::post('/create', [RoleController::class, 'create']);
            Route::patch('/edit/{id}', [RoleController::class, 'update']);
            Route::delete('/delete/{id}', [RoleController::class, 'destroy']);
            Route::get('/permissions', [RoleController::class, 'index']);
            Route::patch('/permissions', [RoleController::class, 'updatePermissions']);
        });
    });
});
