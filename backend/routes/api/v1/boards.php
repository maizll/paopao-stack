<?php

use App\Http\Controllers\Api\V1\Boards\BoardController;
use Illuminate\Support\Facades\Route;

Route::prefix('boards')->middleware('tenant.required')->group(function () {
    Route::get('/', [BoardController::class, 'index']);
    Route::get('{board}', [BoardController::class, 'show'])->whereNumber('board');
    Route::get('{board}/posts', [BoardController::class, 'posts'])->whereNumber('board');
    Route::post('{board}/join', [BoardController::class, 'join'])->middleware(['auth:sanctum', 'tenant.user'])->whereNumber('board');
    Route::delete('{board}/join', [BoardController::class, 'leave'])->middleware(['auth:sanctum', 'tenant.user'])->whereNumber('board');
});
