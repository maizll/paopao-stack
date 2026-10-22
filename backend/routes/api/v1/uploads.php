<?php

use App\Http\Controllers\Api\V1\Uploads\UploadController;
use Illuminate\Support\Facades\Route;

Route::post('uploads', [UploadController::class, 'store'])
    ->middleware(['tenant.required', 'auth:sanctum', 'tenant.user']);
