<?php

use Illuminate\Support\Facades\Route;

/*
| 只挂载模块路由，不写具体业务。
| 新接口进 routes/api/v1/{module}.php，最多在这里加一行 require。
|
| 二期不做、此处不注册：商城、支付、私信、短视频。
| API 规划里标成二期的动作在各模块文件中返回 42201，不落地业务。
*/

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/v1/system.php';
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/users.php';
    require __DIR__.'/api/v1/boards.php';
    require __DIR__.'/api/v1/posts.php';
    require __DIR__.'/api/v1/comments.php';
    require __DIR__.'/api/v1/interactions.php';
    require __DIR__.'/api/v1/apps.php';
    require __DIR__.'/api/v1/app_versions.php';
    require __DIR__.'/api/v1/downloads.php';
    require __DIR__.'/api/v1/ops.php';
    require __DIR__.'/api/v1/reports.php';
    require __DIR__.'/api/v1/notifications.php';
    require __DIR__.'/api/v1/uploads.php';
    require __DIR__.'/api/v1/admin.php';
});
