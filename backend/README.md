# 泡泡栈 API

Laravel 应用，只提供 `/api/v1`。启动方式、演示账号和模块边界见仓库根目录 [README](../README.md)。

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=43125
```

健康检查：`GET /api/v1/system/health`。路由按模块放在 `routes/api/v1/`。
