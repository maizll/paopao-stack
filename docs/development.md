# 泡泡栈开发文档

| 项 | 内容 |
|---|---|
| 文档版本 | 0.3 |
| 日期 | 2026-10-22 |
| 产品 | 泡泡栈 |
| 对齐 | [API 规划](api-planning.md)、[SaaS 补充约定](saas-conventions.md) |

泡泡栈是 SaaS 多租户产品：每个租户有自己的论坛（圈子 / 版块 / 帖子）和软件商店。平台负责开通租户；租户管理员负责内容与上架审核。

## 技术栈

| 部分 | 选择 |
|---|---|
| API | Laravel 13，PHP 8.3，路由前缀 `/api/v1` |
| 鉴权 | Laravel Sanctum personal access token，请求头 `Authorization: Bearer <token>` |
| 用户端 | uni-app（Vue 3 + Vite + TypeScript）+ uni-ui，`apps/uni`。H5 预览，同一套页面可再编到 App / 小程序 |
| 管理后台 | Vue Vben Admin 5.7.0，`apps/admin` 大仓里的 `@vben/web-antd`（Ant Design Vue） |
| 数据库 | MySQL 8 / MariaDB 10.11，字符集 `utf8mb4` |

客户端看到的登录响应是 `access_token`、`token_type`、`expires_in`、`user`。这是冻结的凭证契约。当前签发的是 Sanctum 不透明令牌，不是签名 JWT。以后若换成签名 JWT，只替换 `AuthService::issue()`，不改响应壳，也不改 `Authorization` 头。

错误码 `40102`（Token 过期）已在码表保留。Sanctum 目前把无效和过期都变成未认证，骨架统一返回 `40101`。

## 仓库

```
backend/                 Laravel API
  routes/api.php         只 require 模块文件
  routes/api/v1/         按模块拆分，后台在 admin/
  app/Http/Controllers/Api/V1/{Module}/
  app/Policies/          按模型发现，例如 PostPolicy、TenantPolicy
apps/uni/                用户端（uni-app + uni-ui，H5 / App / 小程序）
apps/admin/              Vue Vben Admin 5.7.0 大仓，运营后台在 apps/web-antd
docs/                    开发文档、API 规划、SaaS 约定
```

## 前端模板

管理后台直接使用 Vue Vben Admin 5.7.0（仓库 [vbenjs/vue-vben-admin](https://github.com/vbenjs/vue-vben-admin)，在线文档 [vben.pro](https://www.vben.pro)）。`apps/admin` 是这份大仓，保留 `packages/` 与 `apps/web-antd`。官方说明 `web-antd` 不能拆出去单独跑，所以没有再手写一套 Pro 布局。启动命令是 `pnpm dev:antd`。登录页是 `AuthenticationLogin`，框架是 `BasicLayout`，列表页是 `Page` + vxe-table。菜单是概览、租户、用户、版块、帖子审核、评论审核、软件审核、运营位、系统。这里不渲染社区信息流。帖子审核列表打开抽屉看正文。编辑是独立页 `/posts/:id/edit`，正文用 Vben 自带的 Tiptap（`VbenTiptap`，`@vben/plugins/tiptap`）写成 HTML，经 `PUT /api/v1/admin/posts/{id}` 保存。状态不在编辑页改。

用户端骨架是 DCloud 官方预设 `uni-preset-vue`（Vite + Vue 3 + TypeScript）和免费的 `@dcloudio/uni-ui`。底部 Tab 是首页、社区、发现、消息、我的。H5 开发端口是 `43126`，`/api` 代理到 Laravel。同一套页面可以再编到 App 和小程序，本轮只跑通 H5。原先的 `apps/web` 已删除。

页面观感按开源笔记社区模板重排，不是手写演示壳。视觉和交互基准是 [HongShu-Ui](https://github.com/Ma-YongJian/HongShu-Ui)（MIT，Copyright (c) 2024 HongShu）里的 `hongshu-app`：首页 `pages/index` 的居中下划线 Tab、分类条和双列流，卡片 `components/hs-note-card`，详情 `pages/main`，个人页 `pages/user`，消息 `pages/message`，发布 `pages/push`。泡泡栈用 Vue 3 `<script setup>` + TypeScript 重写这些页面，数据仍走 `/api/v1`。没有拷贝 HongShu 的 Spring 后端，没有引入 Pro，也没有安装 `thorui-uni`（心形、返回、搜索用 uni-ui 的 `uni-icons`）。[dianwan/uniapp-vue3-xiaohongshu](https://github.com/dianwan/uniapp-vue3-xiaohongshu) 只有双列瀑布流的布局想法，仓库没有许可证，源码没有拷进本仓库。

配色是白底 `#f8fafc`、正文 `#16160e`、强调色 `#ff2442`。首页在双列笔记上方展示已发布 Banner 和宫格。社区是不等高双列封面卡片。发现页只列已通过的软件。H5 在系统状态栏高度为 0 时补一条时间与信号栏。笔记详情可以点赞、收藏和发评论。评论先进入待审，通过后才公开并计入评论数。消息页读取 `/notifications`，展示审核结果、点赞、收藏和评论提醒；「新增关注」仍是空状态，不编造粉丝。发帖封面可以上传图片。

## 模块边界

一个模块 = 一套路由文件 + 一组控制器 + 本模块 Policy。新接口只进本模块，不把业务路由堆进 `routes/api.php`。跨模块只读查询或调用对方服务，不直接写对方的表。

| 模块 | 路由前缀 | 本轮 |
|---|---|---|
| System | `/api/v1/system` | 健康检查、公开配置、协议占位、客户端版本 |
| Auth | `/api/v1/auth` | 注册、登录、刷新、注销、当前用户；找回密码为二期 |
| Users | `/api/v1/users` | 公开资料、改资料、计数、用户帖子；头像上传与关注未做 |
| Boards | `/api/v1/boards` | 已发布版块列表与版块下帖子 |
| Posts | `/api/v1/posts` | 列表、详情、发帖（待审）、作者修改待审帖、软删 |
| Apps | `/api/v1/apps` | 已发布软件、分类、关联帖子 |
| AppVersions | `/api/v1/apps/{id}/versions` | 已发布版本只读 |
| Ops | `/api/v1/ops` | 已发布 Banner、宫格只读；草稿只在管理端 |
| Uploads | `/api/v1/uploads` | 登录用户上传图片到本地 public 磁盘 |
| Comments | `/api/v1/posts/{id}/comments` | 发表后待审；公开列表只含已通过；作者能看到自己的待审和未通过 |
| Interactions | `/api/v1/interactions` | 帖子/评论/软件的赞与藏，重复操作幂等，计数经事件回写 |
| Notifications | `/api/v1/notifications` | 本人通知列表、未读数、已读；推送设备仍是二期 |
| Admin | `/api/v1/admin` | 管理员登录、租户开通、用户封禁、版块维护、帖子审核、评论审核、软件审核、运营位 |
| Tenants | `/api/v1/admin/tenants` | 仅平台超管 |
| Downloads、Reports | 已注册 | 返回 `42201` 骨架占位 |

商城、支付、私信、短视频是二期，不注册路由。API 规划里标明二期的动作（第三方登录、关注、置顶锁帖、评分、下载配额、推送、首页编排、报表、表情回应）同样只返回 `42201`。

## 多租户与角色

解析顺序：

1. 请求头 `X-Tenant-Id`：租户 slug，或数字 id。找不到返回 `40401`。
2. 请求 Host 等于 `tenants.domain`。
3. 子域第一段作为 slug（`demo.paopao.test`、`demo.localhost`）。`www` / `api` / `admin` 忽略。像子域但没有租户时返回 `40401`。
4. 环境变量 `TENANT_DEFAULT_SLUG`。只给本地演示用，生产应留空。

停用或未开通（`status != active`）返回 `42201`。

| 角色 | `users.role` | 范围 |
|---|---|---|
| 平台超管 | `platform_super_admin` | `tenant_id` 为空，可开通租户；管理某个租户的内容时仍要带该租户 |
| 租户管理员 | `tenant_admin` | 只能操作自己的 `tenant_id` |
| 成员 | `member` | 前台登录用户 |

业务模型使用 `BelongsToTenant`。当前请求没有租户时，这些查询不会返回行。`User` 和 `Tenant` 不加这个范围。

## 响应与分页

成功和失败都是：

```json
{ "code": 0, "message": "ok", "data": {} }
```

列表的 `data` 为 `{ "items": [], "meta": { "page": 1, "per_page": 20, "total": 0, "has_more": false } }`。`per_page` 默认 20，超过 50 截断并记日志。排序用 `sort` 与 `order`，字段由各模块白名单决定。

错误码只使用 API 规划 §1.4。实现类是 `App\Support\ApiCode` 与 `App\Support\ApiResponse`。没有 `storage/app/install.lock` 时，站点进入 `/install`，API 返回 `50301`。安装步骤见 [安装说明](install.md)。

## 本轮不做的业务

- 下载签发、举报工单落库
- 软件安装包上传与版本发布写入
- 头像专用接口（封面和运营图走通用上传）
- 商城、支付、私信、短视频

本地演示数据里已有已发布帖子、一个示例软件和一组运营位。成员发帖后状态为 `pending`，正文是纯文本。租户管理员在编辑页用 Tiptap 改成 HTML，再 `PUT /api/v1/admin/posts/{id}`。封面和正文图片走 `POST /api/v1/uploads`，文件落在 `storage/app/public/uploads/{租户}`，返回的地址挂在 `APP_URL/storage` 下。本机需要先执行 `php artisan storage:link`，符号链接不进仓库。`content` 最长 20000 字符，列表摘要用 `strip_tags`。通过后用户端详情用富文本渲染 HTML。

软件在后台新建后是 `draft`，`POST /admin/apps/{id}/approve` 之后才出现在发现页；拒绝后前台列表不再返回。运营位草稿同样不会出现在首页。首页读取 `/ops/banners` 和 `/ops/grid`。

赞和藏走 `/interactions/like` 与 `/interactions/favorite`，重复提交不增加计数。评论走 `POST /posts/{id}/comments`，新评论是 `pending`。后台 `POST /admin/comments/{id}/approve` 之后，笔记下的公开列表和 `comment_count` 才会变化。帖子通过/拒绝、点赞、收藏、评论通过/拒绝会写成通知，用户在 `/notifications` 读取。楼中楼列表、表情回应和推送设备仍返回 `42201`。
