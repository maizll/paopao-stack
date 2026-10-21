<?php

use App\Http\Controllers\Api\V1\Admin\StatsController;
use Illuminate\Support\Facades\Route;

Route::prefix('stats')->middleware(['auth:sanctum', 'role:platform_super_admin'])->group(function () {
    Route::get('overview', [StatsController::class, 'overview']);
});
