<?php

use App\Http\Controllers\Api\V1\Admin\BoardController;
use Illuminate\Support\Facades\Route;

Route::prefix('boards')->middleware(['tenant.required', 'auth:sanctum', 'tenant.user', 'role:tenant_admin,platform_super_admin'])->group(function () {
    Route::get('/', [BoardController::class, 'index']);
    Route::post('/', [BoardController::class, 'store']);
    Route::patch('{board}', [BoardController::class, 'update'])->whereNumber('board');
    Route::delete('{board}', [BoardController::class, 'destroy'])->whereNumber('board');
});
