<?php

use App\Http\Controllers\Api\V1\Apps\AppController;
use Illuminate\Support\Facades\Route;

Route::prefix('apps')->middleware('tenant.required')->group(function () {
    Route::get('categories', [AppController::class, 'categories']);
    Route::get('/', [AppController::class, 'index']);
    Route::get('{app}', [AppController::class, 'show'])->whereNumber('app');
    Route::get('{app}/related-posts', [AppController::class, 'relatedPosts'])->whereNumber('app');
    Route::get('{app}/rating', [AppController::class, 'rating'])->whereNumber('app');
    Route::post('{app}/rating', [AppController::class, 'rating'])->middleware(['auth:sanctum', 'tenant.user'])->whereNumber('app');
});
