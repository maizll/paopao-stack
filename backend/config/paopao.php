<?php

return [

    'name' => '泡泡栈',

    'version' => '0.1.0',

    /*
    | 当前代码自带的服务端版本。页面取这个文件和 storage/app/server-version.json 里较新的一个。
    | 宝塔解压后即使配置被缓存，也会直接读这份文件，避免已安装版本停在旧数字。
    | 0.1.52（version_code 1520）运营后台 App 形态改成两步向导：勾选内置页/管理子页/添加自定义页，再选每页皮肤与底栏；扩展 customPages、subpages。不改表。1520 必须大于已上线的 1510，不要写成 152。
    | 0.1.44（version_code 1440）App 形态恢复 1.3.3 起的页面模板：首页信息流/紧凑/杂志（以及后来的单列、双列、HaoSNS），社区版块/紧凑/论坛，发现货架/宫格/列表，商城卡片/紧凑/海报，消息列表/紧凑/气泡，我的清单/卡片/宫格。不再把旧样式折回两套。不改表。1440 必须大于已上线的 1430，不要写成 144。
    | 0.1.43（version_code 1430）管理员重置他人密码时可填自定义新密码；留空仍随机生成。未配发信时接口仍返回最终密码。不改表。1430 必须大于已上线的 1420，不要写成 143。
    | 0.1.42（version_code 1420）补 GET /api/v1/menu/all，后台不再因菜单 404 进不了概览。不改表。1420 必须大于已上线的 1410，不要写成 142。
    | 0.1.41（version_code 1410）个人中心可改自己的登录邮箱和密码；社区用户页可搜索、筛选、分页，并封禁、重置密码、修改邮箱和新建账号。不改表。1410 必须大于已上线的 1400；之后继续往上写，例如 1420、1430，不要写成 141 或 142。
    | 0.1.40（version_code 1400）快捷入口和宫格短标签在保存时限制字数。1400 必须大于已上线的 1390。
    | 0.1.39（version_code 1390）用户协议和隐私政策、软件评价与相关推荐、租户 SMTP。1390 必须大于已上线的 1380。
    | 0.1.38（version_code 1380）邮箱注册、登录后改密码、邮箱验证码重置密码。1380 必须大于已上线的 1370。
    | 0.1.37（version_code 137）后台 App 形态改成竖屏真机预览，样式和底栏横排卡片；每日抽奖是完整管理页。登录请求 8 秒超时，工作台慢时会提示已经登录成功。
    | 0.1.36（version_code 136）抽奖有公开接口 /api/v1/lottery；版本接口在库里没有 sha256 时按版本或下载地址回填，同步不会把已有校验值写成空。后台 App 形态用竖屏手机预览当前租户内容。
    | 0.1.35（version_code 135）租户可勾选页面、混搭皮肤和底栏，未启用页不进底栏。
    | 0.1.34（version_code 134）在线补丁可以写入 plugins/。0.1.33 整包因此被拒，没有装上。
    | 引导包 0.1.33.1 只换这份白名单，version_code 仍写 133。不要写成 13301，否则 134 会被当成旧版。
    | 0.1.33（version_code 133）补插件注册、生命周期钩子、按插件隔离的权限和存储。
    */
    'server_version' => '0.1.52',

    'server_version_code' => 1520,

    /*
    | 邮箱验证码。MAIL_MAILER 为 log 或 array 时不发信。
    | expose_code 只在未配发信、且为本地或测试时默认打开，接口带回 preview_code。正式 smtp 不要打开。
    */
    'auth' => [
        'code_ttl' => (int) env('PAOPAO_AUTH_CODE_TTL', 600),
        'resend_seconds' => (int) env('PAOPAO_AUTH_RESEND_SECONDS', 60),
        'hourly_limit' => (int) env('PAOPAO_AUTH_HOURLY_LIMIT', 8),
        'max_attempts' => (int) env('PAOPAO_AUTH_MAX_ATTEMPTS', 5),
        'expose_code' => filter_var(
            env('PAOPAO_AUTH_EXPOSE_CODE', in_array(env('APP_ENV', 'production'), ['local', 'testing'], true) ? 'true' : 'false'),
            FILTER_VALIDATE_BOOL,
        ),
    ],

    /*
    | 插件目录。每个子目录是一个插件，入口是 Plugin.php。没有 plugin.json 时，后台「检测并生成」会写一份。
    */
    'plugins_path' => env('PAOPAO_PLUGINS_PATH', base_path('plugins')),

    /*
    | 服务端在线更新。先读公开 raw server.json，失败再读下面的地址（默认是 GitHub Contents API）。
    | SERVER_UPDATE_TOKEN 优先，没有则用 GITHUB_TOKEN。Token 只发给 github.com / api.github.com / raw.githubusercontent.com。
    */
    'server_update' => [
        'manifest_url' => env(
            'SERVER_UPDATE_MANIFEST_URL',
            'https://api.github.com/repos/maizll/paopao-stack-app/contents/server.json?ref=main',
        ),
        'token' => env('SERVER_UPDATE_TOKEN', env('GITHUB_TOKEN')),
        'root' => env('SERVER_UPDATE_ROOT'),
        'max_bytes' => 50 * 1024 * 1024,
        // 0.1.26 整包约 445 个文件，主要是 public/admin。旧上限 400 会报「补丁文件太多」。
        'max_entries' => (int) env('SERVER_UPDATE_MAX_ENTRIES', 2000),
    ],

    /*
    | 本地可设为 demo，使未带 X-Tenant-Id、也没有租户域名的请求落到演示租户。
    | 生产环境应留空，只信任域名 / 子域或请求头。
    */
    'default_tenant_slug' => env('TENANT_DEFAULT_SLUG'),

    'register_enabled' => true,

    'force_login_download' => true,

    'upload_max_mb' => 20,

    /*
    | 笔记视频。一条帖子只能有一条视频，和九宫格图片互斥。
    | 不转码，只限制时长和大小。
    */
    'video_max_mb' => 30,

    'video_max_seconds' => 60,

    /*
    | 安装锁。文件存在表示向导已完成，/install 只显示结果，API 不再拦截。
    */
    'install_lock' => storage_path('app/install.lock'),

    /*
    | 虚拟商城支付。
    | MALL_PAY_DRIVER=sandbox：没有商户密钥时的默认值。App 点确认后走模拟回调，自动发码，不产生真实扣款。
    | MALL_PAY_DRIVER=alipay：需要 ALIPAY_APP_ID、ALIPAY_PRIVATE_KEY、ALIPAY_PUBLIC_KEY。回调验 RSA2。
    | MALL_PAY_DRIVER=wechat：需要 WECHAT_PAY_APP_ID、WECHAT_PAY_MCH_ID、WECHAT_PAY_API_V3_KEY。
    |   微信平台证书下单留到接正式商户时再补；现在用 API v3 密钥对 out_trade_no|transaction_id 做 HMAC-SHA256 校验回调。
    | MALL_PAY_NOTIFY_TOKEN：模拟回调口令。生产如果仍开着 sandbox 通知地址，必须改掉默认值。
    | 多规格 SKU、实体物流、售后退货是二期。接口会返回 available=false 和说明。
    */
    'mall' => [
        'pay_driver' => env('MALL_PAY_DRIVER', 'sandbox'),
        'notify_token' => env('MALL_PAY_NOTIFY_TOKEN', 'paopao-sandbox'),
        'alipay' => [
            'app_id' => env('ALIPAY_APP_ID'),
            'private_key' => env('ALIPAY_PRIVATE_KEY'),
            'public_key' => env('ALIPAY_PUBLIC_KEY'),
            'notify_url' => env('ALIPAY_NOTIFY_URL'),
            'gateway' => env('ALIPAY_GATEWAY', 'https://openapi-sandbox.dl.alipaydev.com/gateway.do'),
        ],
        'wechat' => [
            'app_id' => env('WECHAT_PAY_APP_ID'),
            'mch_id' => env('WECHAT_PAY_MCH_ID'),
            'api_v3_key' => env('WECHAT_PAY_API_V3_KEY'),
            'notify_url' => env('WECHAT_PAY_NOTIFY_URL'),
        ],
    ],

];
