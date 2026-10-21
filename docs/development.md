# 泡泡栈开发文档

| 项 | 内容 |
|---|---|
| 文档版本 | 0.2 |
| 日期 | 2026-10-21 |
| 产品 | 泡泡栈 |
| 对齐 | [API 规划](api-planning.md)、[SaaS 补充约定](saas-conventions.md) |

泡泡栈是 SaaS 多租户产品：每个租户有自己的论坛（圈子 / 版块 / 帖子）和软件商店。平台负责开通租户；租户管理员负责内容与上架审核。

## 技术栈

| 部分 | 选择 |
|---|---|
| API | Laravel 13，PHP 8.3，路由前缀 `/api/v1` |
| 鉴权 | Laravel Sanctum personal access token，请求头 `Authorization: Bearer <token>` |
| 用户端 | uni-app（Vue 3 + Vite + TypeScript）+ uni-ui，`apps/uni`。H5 预览，同一套页面可再编到 App / 小程序 |
| 管理后台 | Ant Design Vue 4，Pro 式侧栏 + 顶栏 + 工作台，`apps/admin` |
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
apps/admin/              运营后台（Ant Design Vue）
docs/                    开发文档、API 规划、SaaS 约定
```

## 前端模板

管理后台使用 Ant Design Vue 4。布局参照开源 Ant Design Pro：深色侧栏、白色顶栏、浅灰内容区和一块工作台。不引入已停更的 Vue CLI 工程 `ant-design-vue-pro`。菜单是概览、租户、用户、版块、帖子审核、软件审核、运营位、系统。这里不渲染社区信息流；帖子正文只在审核抽屉里打开，数据来自 `GET /api/v1/admin/posts/{id}`。

用户端使用 DCloud 官方预设 `uni-preset-vue`（Vite + Vue 3 + TypeScript）和 `@dcloudio/uni-ui`。底部 Tab 是首页、社区、发现、消息、我的。H5 开发端口是 `43126`，`/api` 代理到 Laravel。同一套页面可以再编到 App 和小程序，本轮只跑通 H5。原先的 `apps/web` 已删除。

视觉是白底和珊瑚强调色 `#ff5c39`，首页与社区用双列笔记卡片。不使用葫芦侠的绿色顶栏，也不复用小红书的标识。消息页会请求通知接口；当前返回骨架占位，页面直接说明审核结果不会推送。

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
| Ops | `/api/v1/ops` | Banner、九宫格只读 |
| Admin | `/api/v1/admin` | 管理员登录、租户开通、用户封禁、版块维护、帖子审核 |
| Tenants | `/api/v1/admin/tenants` | 仅平台超管 |
| Comments、Interactions、Downloads、Reports、Notifications | 已注册 | 返回 `42201` 骨架占位 |

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

错误码只使用 API 规划 §1.4。实现类是 `App\Support\ApiCode` 与 `App\Support\ApiResponse`。

## 本轮不做的业务

- 评论、赞藏、下载签发、举报工单、站内通知的落库
- 软件与版本在后台的创建 / 上下架写入（列表可读）
- 运营位的写入
- 头像上传与对象存储
- 商城、支付、私信、短视频

本地演示数据里已有一个已发布帖子、一个示例软件和一组运营位，用来把前台和审核路径走通：成员发帖后状态为 `pending`，租户管理员调用 `POST /api/v1/admin/posts/{id}/approve` 后才会出现在公开列表。
