<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    require __DIR__.'/admin/auth.php';
    require __DIR__.'/admin/tenants.php';
    require __DIR__.'/admin/users.php';
    require __DIR__.'/admin/boards.php';
    require __DIR__.'/admin/posts.php';
    require __DIR__.'/admin/apps.php';
    require __DIR__.'/admin/ops.php';
    require __DIR__.'/admin/reports.php';
    require __DIR__.'/admin/stats.php';
});
