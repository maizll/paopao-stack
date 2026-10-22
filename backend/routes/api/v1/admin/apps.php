<?php

use App\Http\Controllers\Api\V1\Admin\AppController;
use App\Http\Controllers\Api\V1\Admin\AppVersionController;
use Illuminate\Support\Facades\Route;

$admin = ['tenant.required', 'auth:sanctum', 'tenant.user', 'role:tenant_admin,platform_super_admin'];

Route::prefix('apps')->middleware($admin)->group(function () {
    Route::get('/', [AppController::class, 'index']);
    Route::post('/', [AppController::class, 'store']);
    Route::get('{app}', [AppController::class, 'show'])->whereNumber('app');
    Route::match(['put', 'patch'], '{app}', [AppController::class, 'update'])->whereNumber('app');
    Route::post('{app}/approve', [AppController::class, 'approve'])->whereNumber('app');
    Route::post('{app}/reject', [AppController::class, 'reject'])->whereNumber('app');
});

Route::prefix('app-versions')->middleware($admin)->group(function () {
    Route::get('/', [AppVersionController::class, 'index']);
    Route::post('/', [AppVersionController::class, 'store']);
    Route::patch('{version}', [AppVersionController::class, 'update'])->whereNumber('version');
});
