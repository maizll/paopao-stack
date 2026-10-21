<?php

use App\Http\Controllers\Api\V1\System\SystemController;
use Illuminate\Support\Facades\Route;

Route::prefix('system')->group(function () {
    Route::get('health', [SystemController::class, 'health'])->name('api.v1.system.health');
    Route::get('config', [SystemController::class, 'config']);
    Route::get('agreements/{type}', [SystemController::class, 'agreement']);
    Route::get('client-version', [SystemController::class, 'clientVersion']);
    Route::get('time', [SystemController::class, 'time']);
});
