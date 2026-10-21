<?php

use App\Http\Controllers\Api\V1\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->middleware(['tenant.required', 'auth:sanctum', 'tenant.user', 'role:tenant_admin,platform_super_admin'])->group(function () {
    Route::get('/', [ReportController::class, 'index']);
    Route::post('{id}/resolve', [ReportController::class, 'resolve'])->whereNumber('id');
});
