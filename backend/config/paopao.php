<?php

return [

    'name' => '泡泡栈',

    'version' => '0.1.0',

    /*
    | 本地可设为 demo，使未带 X-Tenant-Id、也没有租户域名的请求落到演示租户。
    | 生产环境应留空，只信任域名 / 子域或请求头。
    */
    'default_tenant_slug' => env('TENANT_DEFAULT_SLUG'),

    'register_enabled' => true,

    'force_login_download' => true,

    'upload_max_mb' => 20,

    /*
    | 安装锁。文件存在表示向导已完成，/install 只显示结果，API 不再拦截。
    */
    'install_lock' => storage_path('app/install.lock'),

];
