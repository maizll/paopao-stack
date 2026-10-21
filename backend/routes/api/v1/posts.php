<?php

use App\Http\Controllers\Api\V1\Posts\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('posts')->middleware('tenant.required')->group(function () {
    Route::get('/', [PostController::class, 'index']);
    Route::post('/', [PostController::class, 'store'])->middleware(['auth:sanctum', 'tenant.user']);
    Route::get('{post}', [PostController::class, 'show'])->middleware('auth.optional')->whereNumber('post');
    Route::patch('{post}', [PostController::class, 'update'])->middleware(['auth:sanctum', 'tenant.user'])->whereNumber('post');
    Route::delete('{post}', [PostController::class, 'destroy'])->middleware(['auth:sanctum', 'tenant.user'])->whereNumber('post');
    Route::post('{post}/pin', [PostController::class, 'pin'])->middleware(['auth:sanctum', 'tenant.user'])->whereNumber('post');
    Route::post('{post}/lock', [PostController::class, 'lock'])->middleware(['auth:sanctum', 'tenant.user'])->whereNumber('post');
});
