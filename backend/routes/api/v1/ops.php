<?php

use App\Http\Controllers\Api\V1\Ops\OpsController;
use Illuminate\Support\Facades\Route;

Route::prefix('ops')->middleware('tenant.required')->group(function () {
    Route::get('banners', [OpsController::class, 'banners']);
    Route::get('grid', [OpsController::class, 'grid']);
    Route::get('home-layout', [OpsController::class, 'homeLayout']);
    Route::get('popups', [OpsController::class, 'popups']);
});
