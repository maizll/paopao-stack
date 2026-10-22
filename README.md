# 泡泡栈

SaaS 多租户「论坛 + 软件商店」。一个租户一套圈子和软件目录，平台超管负责开通，租户管理员负责审核。

本仓库是可运行骨架：统一 API 契约、租户解析、模块路由，以及用户端 / 管理后台的最小界面。商城、支付、私信、短视频不在本轮。

## 目录

| 路径 | 内容 |
|---|---|
| `backend/` | Laravel API，`/api/v1` |
| `apps/uni/` | uni-app + uni-ui 用户端（H5，可再编 App / 小程序） |
| `apps/admin/` | Vue Vben Admin 5.7.0（`@vben/web-antd`）运营后台 |
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
corepack enable
pnpm install
pnpm dev:antd
```

| 服务 | 地址 |
|---|---|
| API | http://127.0.0.1:43125 |
| 健康检查 | http://127.0.0.1:43125/api/v1/system/health |
| 用户端 H5 | http://127.0.0.1:43126 |
| 管理后台 | http://127.0.0.1:43124 |
| 安装向导 | http://127.0.0.1:43125/install |

还没有 `backend/storage/app/install.lock` 时，打开站点或 API 会进入安装向导。已经用上面的命令迁移过的本机库，向导里不要勾选演示数据，避免覆盖示例帖。步骤见 [docs/install.md](docs/install.md)。

用户端骨架是 DCloud 官方 `uni-preset-vue`（Vite + Vue 3 + TypeScript）加上免费的 `uni-ui`。笔记流的版式参考 MIT 协议的 [HongShu-Ui](https://github.com/Ma-YongJian/HongShu-Ui) `hongshu-app`（首页、详情、个人页、消息、发布和笔记卡片），用本仓库的 Vue 3 + TypeScript 页面重写，并继续请求 `/api/v1`。没有引入它的 Spring 后端、Pro 或 `thorui-uni`。底部 Tab 为首页、社区、发现、消息、我的。H5 使用 hash 路由，开发时 `/api` 代理到 `43125`。生产构建读取 `apps/uni/.env.production` 的 `VITE_API_BASE`，把接口写成公网地址；这个值目前是临时 Cloudflare Quick Tunnel，进程退出或域名轮换后失效。本地 `npm run dev:h5` 不读该文件。要预览微信小程序，在 `apps/uni` 执行 `npm run dev:mp-weixin`，再用开发者工具打开 `dist/dev/mp-weixin`。本仓库这一轮只保证 H5 能跑。

管理后台是 [Vue Vben Admin](https://github.com/vbenjs/vue-vben-admin) **v5.7.0** 整仓，跑的是 Ant Design Vue 应用 `@vben/web-antd`（`pnpm dev:antd`，端口 43124）。登录页、侧栏、顶栏、多页签、主题和列表用的是 Vben 自带的 `BasicLayout`、`AuthenticationLogin`、`Page` 和 vxe-table，不是手写的仿 Pro 壳。菜单只保留运营：概览、租户、用户、版块、帖子审核、软件审核、运营位、系统。社区信息流不在后台。

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

成员在用户端「社区」里写一篇，帖子进入待审。租户管理员在「帖子审核」点编辑，进入 `/posts/:id/edit` 整页表单，用 Tiptap 改标题、版块、封面和 HTML 正文，再回列表通过。封面和正文图片走 `POST /api/v1/uploads`，文件落在本机 `storage/app/public`。通过之后，帖子才会出现在首页和社区。

软件在「软件审核」里新建后是待审，通过才会出现在用户端「发现」。首页 Banner 和宫格在「运营位」里维护，只有已发布的会展示。

笔记详情可以点赞和收藏，数量写回帖子。评论提交后是待审，租户管理员在「评论审核」通过后才会公开。审核结果、点赞、收藏和评论提醒出现在用户端「消息」。

## 鉴权说明

响应字段按 Bearer 访问令牌约定：`access_token`、`token_type`、`expires_in`。实现是 Laravel Sanctum 的 personal access token，默认 24 小时。这不是签名 JWT；替换签发方式时不要改响应壳。
