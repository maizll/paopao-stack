<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware(['tenant.required', 'throttle:20,1']);
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware(['auth:sanctum', 'tenant.user']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'tenant.user']);
    Route::get('me', [AuthController::class, 'me'])->middleware(['auth:sanctum', 'tenant.user']);
    Route::post('password/forgot', [AuthController::class, 'forgot']);
    Route::post('password/reset', [AuthController::class, 'reset']);
});
