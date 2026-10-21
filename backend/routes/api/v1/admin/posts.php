<?php

use App\Http\Controllers\Api\V1\Admin\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('posts')->middleware(['tenant.required', 'auth:sanctum', 'tenant.user', 'role:tenant_admin,platform_super_admin'])->group(function () {
    Route::get('/', [PostController::class, 'index']);
    Route::get('{post}', [PostController::class, 'show'])->whereNumber('post');
    Route::match(['put', 'patch'], '{post}', [PostController::class, 'update'])->whereNumber('post');
    Route::post('{post}/approve', [PostController::class, 'approve'])->whereNumber('post');
    Route::post('{post}/reject', [PostController::class, 'reject'])->whereNumber('post');
    Route::delete('{post}', [PostController::class, 'destroy'])->whereNumber('post');
});
