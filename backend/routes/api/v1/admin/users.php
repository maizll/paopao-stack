<?php

use App\Http\Controllers\Api\V1\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->middleware(['tenant.required', 'auth:sanctum', 'tenant.user', 'role:tenant_admin,platform_super_admin'])->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('{id}/ban', [UserController::class, 'ban'])->whereNumber('id');
    Route::post('{id}/unban', [UserController::class, 'unban'])->whereNumber('id');
});
