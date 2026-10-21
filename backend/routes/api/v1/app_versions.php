<?php

use App\Http\Controllers\Api\V1\Apps\AppVersionController;
use Illuminate\Support\Facades\Route;

Route::middleware('tenant.required')->group(function () {
    Route::get('apps/{app}/versions', [AppVersionController::class, 'index'])->whereNumber('app');
    Route::get('apps/{app}/versions/latest', [AppVersionController::class, 'latest'])->whereNumber('app');
    Route::get('app-versions/{version}', [AppVersionController::class, 'show'])->whereNumber('version');
    Route::get('app-versions/{version}/changelog', [AppVersionController::class, 'changelog'])->whereNumber('version');
});
