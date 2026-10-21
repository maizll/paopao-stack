<?php

use App\Http\Controllers\Api\V1\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->middleware('tenant.required')->group(function () {
    Route::get('me/stats', [UserController::class, 'stats'])->middleware(['auth:sanctum', 'tenant.user']);
    Route::patch('me', [UserController::class, 'updateMe'])->middleware(['auth:sanctum', 'tenant.user']);
    Route::post('me/avatar', [UserController::class, 'avatar'])->middleware(['auth:sanctum', 'tenant.user']);
    Route::get('{id}/posts', [UserController::class, 'posts'])->middleware('auth.optional')->whereNumber('id');
    Route::post('{id}/follow', [UserController::class, 'follow'])->middleware(['auth:sanctum', 'tenant.user'])->whereNumber('id');
    Route::delete('{id}/follow', [UserController::class, 'unfollow'])->middleware(['auth:sanctum', 'tenant.user'])->whereNumber('id');
    Route::get('{id}', [UserController::class, 'show'])->whereNumber('id');
});
