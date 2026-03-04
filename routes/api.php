<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Client\AuthController;

Route::prefix('auth')->group(function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api_user');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth:api_user');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api_user');
});
