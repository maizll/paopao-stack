<?php

use App\Http\Controllers\Api\V1\Interactions\InteractionController;
use Illuminate\Support\Facades\Route;

Route::prefix('interactions')->middleware(['tenant.required', 'auth:sanctum', 'tenant.user'])->group(function () {
    Route::post('like', [InteractionController::class, 'like']);
    Route::delete('like', [InteractionController::class, 'unlike']);
    Route::post('favorite', [InteractionController::class, 'favorite']);
    Route::delete('favorite', [InteractionController::class, 'unfavorite']);
    Route::get('favorites', [InteractionController::class, 'favorites']);
    Route::get('status', [InteractionController::class, 'status']);
    Route::post('reaction', [InteractionController::class, 'reaction']);
});
