<?php

use App\Http\Controllers\Api\V1\Reports\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->middleware(['tenant.required', 'auth:sanctum', 'tenant.user'])->group(function () {
    Route::post('/', [ReportController::class, 'store']);
    Route::get('mine', [ReportController::class, 'mine']);
    Route::get('{id}', [ReportController::class, 'show'])->whereNumber('id');
});
