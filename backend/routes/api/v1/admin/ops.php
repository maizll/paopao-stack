<?php

use App\Http\Controllers\Api\V1\Admin\OpsController;
use Illuminate\Support\Facades\Route;

Route::prefix('ops')->middleware(['tenant.required', 'auth:sanctum', 'tenant.user', 'role:tenant_admin,platform_super_admin'])->group(function () {
    Route::get('banners', [OpsController::class, 'banners']);
    Route::post('banners', [OpsController::class, 'storeBanner']);
    Route::match(['put', 'patch'], 'banners/{banner}', [OpsController::class, 'updateBanner'])->whereNumber('banner');
    Route::delete('banners/{banner}', [OpsController::class, 'destroyBanner'])->whereNumber('banner');
    Route::get('grid', [OpsController::class, 'grid']);
    Route::post('grid', [OpsController::class, 'storeGrid']);
    Route::match(['put', 'patch'], 'grid/{gridItem}', [OpsController::class, 'updateGrid'])->whereNumber('gridItem');
    Route::delete('grid/{gridItem}', [OpsController::class, 'destroyGrid'])->whereNumber('gridItem');
});
