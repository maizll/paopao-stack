<?php

use App\Http\Controllers\Api\V1\Notifications\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->middleware(['tenant.required', 'auth:sanctum', 'tenant.user'])->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('read-all', [NotificationController::class, 'readAll']);
    Route::post('device', [NotificationController::class, 'device']);
    Route::post('{id}/read', [NotificationController::class, 'read'])->whereNumber('id');
});
