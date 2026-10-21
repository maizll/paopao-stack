<?php

use App\Http\Controllers\Api\V1\Admin\OpsController;
use Illuminate\Support\Facades\Route;

Route::prefix('ops')->middleware(['tenant.required', 'auth:sanctum', 'tenant.user', 'role:tenant_admin,platform_super_admin'])->group(function () {
    Route::get('banners', [OpsController::class, 'banners']);
    Route::post('banners', [OpsController::class, 'storeBanner']);
    Route::get('grid', [OpsController::class, 'grid']);
    Route::post('grid', [OpsController::class, 'storeGrid']);
});
