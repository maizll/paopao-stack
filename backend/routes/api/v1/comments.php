<?php

use App\Http\Controllers\Api\V1\Comments\CommentController;
use Illuminate\Support\Facades\Route;

Route::middleware('tenant.required')->group(function () {
    Route::get('posts/{post}/comments', [CommentController::class, 'index'])->middleware('auth.optional')->whereNumber('post');
    Route::post('posts/{post}/comments', [CommentController::class, 'store'])->middleware(['auth:sanctum', 'tenant.user'])->whereNumber('post');
    Route::get('comments/{comment}/replies', [CommentController::class, 'replies'])->whereNumber('comment');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->middleware(['auth:sanctum', 'tenant.user'])->whereNumber('comment');
});
