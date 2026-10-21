# 泡泡栈

SaaS 多租户「论坛 + 软件商店」。一个租户一套圈子和软件目录，平台超管负责开通，租户管理员负责审核。

本仓库是可运行骨架：统一 API 契约、租户解析、模块路由，以及用户端 / 管理后台的最小界面。商城、支付、私信、短视频不在本轮。

## 目录

| 路径 | 内容 |
|---|---|
| `backend/` | Laravel API，`/api/v1` |
| `apps/uni/` | uni-app + uni-ui 用户端（H5，可再编 App / 小程序） |
| `apps/admin/` | Ant Design Vue 运营后台 |
| `docs/` | [开发文档](docs/development.md)、[API 规划](docs/api-planning.md)、[SaaS 约定](docs/saas-conventions.md) |

模块边界、错误码和二期清单以 `docs/` 为准。

## 本地启动

需要 PHP 8.3、Composer、Node.js 22、MySQL 或 MariaDB。

```bash
mysql -e "CREATE DATABASE IF NOT EXISTS paopao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# 按 backend/.env.example 准备账号。示例：用户 paopao / 密码 paopao，库名 paopao。

cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=43125

cd ../apps/uni
npm install
npm run dev:h5

cd ../admin
npm install
npm run dev
```

| 服务 | 地址 |
|---|---|
| API | http://127.0.0.1:43125 |
| 健康检查 | http://127.0.0.1:43125/api/v1/system/health |
| 用户端 H5 | http://127.0.0.1:43126 |
| 管理后台 | http://127.0.0.1:43124 |

用户端是 DCloud 官方 `uni-preset-vue`（Vite + Vue 3 + TypeScript）加上 `uni-ui`，底部 Tab 为首页、社区、发现、消息、我的。H5 使用 hash 路由，`/api` 代理到 `43125`。要预览微信小程序，在 `apps/uni` 执行 `npm run dev:mp-weixin`，再用开发者工具打开 `dist/dev/mp-weixin`。本仓库这一轮只保证 H5 能跑。

管理后台用 Ant Design Vue 4，布局按开源 Ant Design Pro：深色侧栏、顶栏、工作台。没有引入已停更的 Vue CLI 版 Pro 模板。菜单是运营菜单（概览、租户、用户、版块、帖子审核、软件审核、运营位、系统），不展示社区内容流。帖子正文只在审核抽屉里打开。

原先的 `apps/web` Vue H5 已移除，用户端只保留 `apps/uni`。

路由清单：

```bash
cd backend && php artisan route:list --path=api
```

测试：

```bash
cd backend && php artisan test
```

前端开发服务器把 `/api` 代理到 `43125`。直接调 API 时带上 `X-Tenant-Id: demo`。本地 `.env` 里的 `TENANT_DEFAULT_SLUG=demo` 只是演示兜底，生产应留空。

## 演示账号

密码都是 `password`。

| 账号 | 角色 | 租户头 |
|---|---|---|
| `super@paopao.local` | 平台超管 | 可不带；管理演示内容时用 `demo` |
| `admin@demo.paopao.local` | 租户管理员 | `demo` |
| `member@demo.paopao.local` | 成员 | `demo` |

成员在用户端「社区」里写一篇，帖子进入待审。租户管理员在后台「帖子审核」里通过之后，帖子才会出现在首页和社区。

## 鉴权说明

响应字段按 Bearer 访问令牌约定：`access_token`、`token_type`、`expires_in`。实现是 Laravel Sanctum 的 personal access token，默认 24 小时。这不是签名 JWT；替换签发方式时不要改响应壳。
