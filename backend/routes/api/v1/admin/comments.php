<?php

use App\Http\Controllers\Api\V1\Admin\CommentController;
use Illuminate\Support\Facades\Route;

Route::prefix('comments')->middleware(['tenant.required', 'auth:sanctum', 'tenant.user', 'role:tenant_admin,platform_super_admin'])->group(function () {
    Route::get('/', [CommentController::class, 'index']);
    Route::get('{comment}', [CommentController::class, 'show'])->whereNumber('comment');
    Route::post('{comment}/approve', [CommentController::class, 'approve'])->whereNumber('comment');
    Route::post('{comment}/reject', [CommentController::class, 'reject'])->whereNumber('comment');
    Route::post('{comment}/hide', [CommentController::class, 'hide'])->whereNumber('comment');
    Route::delete('{comment}', [CommentController::class, 'destroy'])->whereNumber('comment');
});
