<?php

use App\Http\Controllers\Api\V1\Admin\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('tenants')->middleware(['auth:sanctum', 'role:platform_super_admin'])->group(function () {
    Route::get('/', [TenantController::class, 'index']);
    Route::post('/', [TenantController::class, 'store']);
    Route::get('{tenant}', [TenantController::class, 'show'])->whereNumber('tenant');
    Route::patch('{tenant}', [TenantController::class, 'update'])->whereNumber('tenant');
});
