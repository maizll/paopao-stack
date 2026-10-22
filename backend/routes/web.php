<?php

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('install')->group(function () {
    Route::get('/', [InstallController::class, 'show'])->name('install.show');
    Route::get('/environment', [InstallController::class, 'environment']);
    Route::post('/database/test', [InstallController::class, 'testDatabase']);
    Route::post('/admin', [InstallController::class, 'saveAdmin']);
    Route::post('/site', [InstallController::class, 'saveSite']);
    Route::post('/run', [InstallController::class, 'run']);
});
