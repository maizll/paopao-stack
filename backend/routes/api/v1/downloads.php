<?php

use App\Http\Controllers\Api\V1\Downloads\DownloadController;
use Illuminate\Support\Facades\Route;

Route::prefix('downloads')->middleware(['tenant.required', 'auth:sanctum', 'tenant.user'])->group(function () {
    Route::post('/', [DownloadController::class, 'store']);
    Route::get('mine', [DownloadController::class, 'mine']);
    Route::get('quota', [DownloadController::class, 'quota']);
    Route::get('{id}', [DownloadController::class, 'show'])->whereNumber('id');
    Route::post('{id}/complete', [DownloadController::class, 'complete'])->whereNumber('id');
});
