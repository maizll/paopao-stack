# 论坛软件商店 · API 规划

| 项 | 内容 |
|---|---|
| 文档版本 | **0.1** |
| 日期 | **2026-10-21**（Asia/Shanghai） |
| 作者 | **软件库** |
| 技术栈 | Laravel（API）+ Vue3 H5 |
| 对齐文档 | `docs/development.md`（开发文档）。后续对齐时只增改本文件各模块节，不改全局契约 |
| 文档性质 | **可维护的接口规划**（不是实现、不是 OpenAPI 全文） |

---

## 1. 设计原则

### 1.1 资源化 REST

- 路径用名词复数表示资源：`/posts`、`/apps/{id}`，动作用 HTTP Method。
- 子资源挂在父资源下：`/posts/{id}/comments`、`/apps/{id}/versions`。
- 非 CRUD 动作用子路径动词（少用）：`/downloads/{id}/complete`、`/auth/logout`。
- 禁止在查询串里塞「动作名」当业务入口（如 `?action=like`）。

### 1.2 版本前缀

- 对外统一：`/api/v1/...`
- 后台管理可同版本不同前缀：`/api/v1/admin/...`
- 破坏性变更才升 `v2`（见 §5）；非破坏性只在本模块加路由。

### 1.3 统一响应体

所有 JSON 接口（含错误）统一：

```json
{
  "code": 0,
  "message": "ok",
  "data": {}
}
```

| 字段 | 类型 | 说明 |
|---|---|---|
| `code` | int | `0` 成功；非 0 为业务/协议错误码（见 §1.4） |
| `message` | string | 给人看的短文案（可 i18n key，MVP 用中文） |
| `data` | object\|array\|null | 成功时载荷；失败时可为 `null` 或带 `errors` 校验明细 |

列表成功时 `data` 建议固定形态：

```json
{
  "items": [],
  "meta": { "page": 1, "per_page": 20, "total": 0, "has_more": false }
}
```

### 1.4 统一错误码表（全局契约，模块只「选用」不「改表结构」）

| code | HTTP 建议 | 含义 | 谁可扩展 |
|---|---|---|---|
| 0 | 200 | 成功 | — |
| 40001 | 400 | 参数校验失败 | 模块可在 `data.errors` 填字段 |
| 40101 | 401 | 未登录 / Token 无效 | Auth 模块 |
| 40102 | 401 | Token 过期 | Auth 模块 |
| 40301 | 403 | 无权限 | 各模块选用 |
| 40401 | 404 | 资源不存在 | 各模块选用 |
| 40901 | 409 | 状态冲突（重复点赞、版本号冲突等） | 各模块选用 |
| 42201 | 422 | 业务规则不通过（未过审不可下载等） | 各模块选用 |
| 42901 | 429 | 限流 | System / 全局中间件 |
| 50001 | 500 | 未归类服务端错误 | System |
| 50301 | 503 | 系统尚未安装，需走安装向导 | System |

**约定**：新错误码只能在本表追加行；禁止模块私自发明响应壳字段（如 `success`/`errno`）。模块业务码建议按「模块号段」预留（二期）：Auth `41xxx`、Users `42xxx`、Posts `43xxx`… MVP 先用上表通用码即可。

### 1.5 鉴权

- 方案：**Bearer JWT**（`Authorization: Bearer <token>`）。
- 公开接口：浏览帖/软件列表详情、运营位、部分系统配置。
- 登录用户接口：发帖、评论、赞藏、下载登记、举报、通知等。
- 管理端接口：`auth:admin`（或角色中间件），路径均在 `/api/v1/admin/...`。
- **禁止**：为单个新接口改全局 JWT 载荷契约；新声明只进本模块 Policy / Gate。

### 1.6 分页约定

| 参数 | 默认 | 上限 | 说明 |
|---|---|---|---|
| `page` | 1 | — | 从 1 开始 |
| `per_page` | 20 | 50 | 超过上限按上限截断并记日志 |

排序统一：`sort=field` + `order=asc|desc`（白名单字段，各模块自表）。游标分页（`cursor`）留给二期高流量列表。

### 1.7 模块边界（核心维护约定）

1. **一个模块 = 一套路由文件 + 一组控制器 + 本模块 Policy**，目录建议：`routes/api/v1/{module}.php`、`app/Http/Controllers/Api/V1/{Module}/`。
2. **新接口只进本模块路由/控制器**；禁止为「方便」改全局 `Kernel` / 统一中间件契约 / 公共 `BaseController` 响应格式。
3. **跨模块只走领域服务或只读查询**；**禁止跨模块直接写他表**（见各模块「禁止」小节）。
4. 共享 DTO / Resource 可抽，但不得反向依赖具体业务模块。
5. 变更写进本文件对应模块「接口表」+ §5 Changelog；不靠口头。

---

## 2. 模块总览

| # | 模块 | 路由前缀 | MVP | 二期 |
|---|---|---|---|---|
| 1 | Auth 认证 | `/api/v1/auth` | ✓ | 第三方登录等 |
| 2 | Users 用户 | `/api/v1/users` | ✓ | 关注、主页扩展 |
| 3 | Boards 圈子/版块 | `/api/v1/boards` | ✓ | 版主工具 |
| 4 | Posts 帖子 | `/api/v1/posts` | ✓ | 草稿/定时/话题 |
| 5 | Comments 评论 | `/api/v1/comments` 及帖下挂载 | ✓ | 楼中楼折叠优化 |
| 6 | Interactions 互动 | `/api/v1/interactions` | ✓ | 表情回应 |
| 7 | Apps 软件 | `/api/v1/apps` | ✓ | 付费/评分聚合 |
| 8 | AppVersions 软件版本 | `/api/v1/app-versions` 及软件下挂载 | ✓ | 差分包 |
| 9 | Downloads 下载 | `/api/v1/downloads` | ✓ | 配额/加速 |
| 10 | Ops 运营位 | `/api/v1/ops` | ✓ | 定向投放 |
| 11 | Reports 举报 | `/api/v1/reports` | ✓ | 工作流细化 |
| 12 | Notifications 通知 | `/api/v1/notifications` | ✓ | 推送通道 |
| 13 | Admin 后台 | `/api/v1/admin/...` | ✓ 基础审核 | 数据报表 |
| 14 | System 系统 | `/api/v1/system` | ✓ | 健康检查扩展 |

**模块数：14**

---

## 3. 各模块规划

> 接口表列：**Method · Path · 简述 · 谁可调 · 阶段**  
> Path 均相对版本根；完整 URL = `/api/v1` + Path（Admin 已含 `admin`）。

---

### 3.1 Auth · 认证

**职责边界**  
注册、登录、刷新/注销 Token、找回密码（MVP 可仅预留）、当前会话探测。不负责用户资料编辑（归 Users）。

**路由前缀**：`/api/v1/auth`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| POST | `/auth/register` | 手机号/邮箱+密码注册 | 游客 | MVP |
| POST | `/auth/login` | 登录拿 JWT | 游客 | MVP |
| POST | `/auth/refresh` | 刷新 Access Token | 持 refresh 者 | MVP |
| POST | `/auth/logout` | 注销当前 Token | 登录用户 | MVP |
| GET | `/auth/me` | 当前登录态摘要 | 登录用户 | MVP |
| POST | `/auth/password/forgot` | 发送重置凭证 | 游客 | 二期 |
| POST | `/auth/password/reset` | 重置密码 | 游客（持凭证） | 二期 |

**关键字段要点**

- 请求：`account`（手机/邮箱）、`password`、`device_id`（可选）、`captcha`（若开启）。
- 响应 `data`：`access_token`、`token_type`（`Bearer`）、`expires_in`、`user`（id、nickname、avatar、roles[]）。
- `/auth/me`：`id`、`nickname`、`avatar`、`roles`、`permissions`（精简列表）。

**依赖**：Users（创建/校验账号）  
**禁止**：直接改 `posts`/`apps` 等表；不在登录接口里塞业务配置大包（配置走 System/Ops）。

---

### 3.2 Users · 用户

**职责边界**  
用户公开资料、自己改资料/头像、账号安全信息只读。关注关系二期。

**路由前缀**：`/api/v1/users`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| GET | `/users/{id}` | 公开主页摘要 | 游客/登录 | MVP |
| PATCH | `/users/me` | 改昵称/简介/性别等 | 本人 | MVP |
| POST | `/users/me/avatar` | 上传头像 | 本人 | MVP |
| GET | `/users/me/stats` | 我的发帖/赞藏等计数 | 本人 | MVP |
| GET | `/users/{id}/posts` | 用户帖子列表 | 游客/登录 | MVP |
| POST | `/users/{id}/follow` | 关注 | 登录 | 二期 |
| DELETE | `/users/{id}/follow` | 取消关注 | 登录 | 二期 |

**关键字段要点**

- 公开资料：`id`、`nickname`、`avatar`、`bio`、`created_at`；私密字段仅本人可见。
- PATCH：`nickname`、`bio`、`gender`；禁止客户端改 `roles`/`status`。

**依赖**：Auth（身份）；Posts/Interactions 只读聚合计数  
**禁止**：写 `boards`/`apps`；改他人 `status`（归 Admin）。

---

### 3.3 Boards · 圈子/版块

**职责边界**  
版块树/列表、版块详情、加入/退出（若产品需要）。帖子 CRUD 不在此模块。

**路由前缀**：`/api/v1/boards`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| GET | `/boards` | 版块列表（可树形） | 游客/登录 | MVP |
| GET | `/boards/{id}` | 版块详情 | 游客/登录 | MVP |
| GET | `/boards/{id}/posts` | 版块下帖子（转调 Posts 查询） | 游客/登录 | MVP |
| POST | `/boards/{id}/join` | 加入圈子 | 登录 | 二期 |
| DELETE | `/boards/{id}/join` | 退出圈子 | 登录 | 二期 |

**关键字段要点**

- 列表项：`id`、`name`、`slug`、`icon`、`description`、`post_count`、`sort`、`parent_id`。
- 仅展示 `status=published`；隐藏版对游客 404/403。

**依赖**：Posts（列表查询）  
**禁止**：在此模块插入 `posts` 行；版块增删改走 Admin。

---

### 3.4 Posts · 帖子

**职责边界**  
发帖、编辑、删除（软删）、详情、列表筛选、状态流转由作者有限操作；审核状态变更归 Admin。

**路由前缀**：`/api/v1/posts`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| GET | `/posts` | 帖子列表（board_id/关键词/排序） | 游客/登录 | MVP |
| GET | `/posts/{id}` | 帖子详情（已发布；作者可见自己的待审） | 游客/登录 | MVP |
| POST | `/posts` | 发帖 | 登录 | MVP |
| PATCH | `/posts/{id}` | 编辑（作者；限时或未审核前） | 作者 | MVP |
| DELETE | `/posts/{id}` | 软删 | 作者或版主/管理 | MVP |
| POST | `/posts/{id}/pin` | 置顶 | 版主/管理 | 二期 |
| POST | `/posts/{id}/lock` | 锁帖 | 版主/管理 | 二期 |

**关键字段要点**

- 写：`board_id`、`title`、`content`（或 `content_blocks`）、`cover`、`app_id`（可选关联软件）。
- 读：`id`、`title`、`content`、`author`、`board`、`stats`（view/like/comment/favorite）、`status`、`created_at`。
- 列表查询：`board_id`、`keyword`、`app_id`、`sort=hot|new`。

**依赖**：Boards、Users、可选 Apps；计数可读 Interactions/Comments  
**禁止**：直接改 `comments`/`likes` 表；删帖不物理级联他模块（发领域事件或由监听处理）。

---

### 3.5 Comments · 评论

**职责边界**  
对帖子的评论/回复、删除、列表。点赞评论可挂 Interactions，本模块只提供评论资源。

**路由前缀**：`/api/v1/comments`（创建也允许挂在帖下）

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| GET | `/posts/{post_id}/comments` | 某帖评论列表 | 游客/登录 | MVP |
| POST | `/posts/{post_id}/comments` | 发表评论/回复 | 登录 | MVP |
| DELETE | `/comments/{id}` | 删除评论 | 作者或管理 | MVP |
| GET | `/comments/{id}/replies` | 二级回复列表 | 游客/登录 | 二期 |

**关键字段要点**

- 写：`content`、`parent_id`（可空）、`reply_to_user_id`（可空）。
- 读：`id`、`content`、`author`、`parent_id`、`like_count`、`created_at`、`status`。

**依赖**：Posts、Users；赞数可读 Interactions  
**禁止**：改 `posts.content`；禁止无帖子上下文的孤儿评论写入。

---

### 3.6 Interactions · 互动（赞 / 藏）

**职责边界**  
对帖子、评论、软件等目标的点赞与收藏；查询「我是否已赞/藏」。不负责发帖。

**路由前缀**：`/api/v1/interactions`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| POST | `/interactions/like` | 点赞（幂等） | 登录 | MVP |
| DELETE | `/interactions/like` | 取消赞 | 登录 | MVP |
| POST | `/interactions/favorite` | 收藏 | 登录 | MVP |
| DELETE | `/interactions/favorite` | 取消收藏 | 登录 | MVP |
| GET | `/interactions/favorites` | 我的收藏列表 | 本人 | MVP |
| GET | `/interactions/status` | 批量查对目标的赞藏状态 | 登录 | MVP |
| POST | `/interactions/reaction` | 表情回应 | 登录 | 二期 |

**关键字段要点**

- 写：`target_type`（`post|comment|app`）、`target_id`。
- `status` 查询：`targets[]` → 返回 `liked`/`favorited` map。
- 列表：回挂目标摘要（标题、封面），由本模块组装只读 DTO。

**依赖**：Posts / Comments / Apps（存在性校验，只读）  
**禁止**：直接 UPDATE 他模块计数列（通过事件/服务递增）；禁止删帖。

---

### 3.7 Apps · 软件

**职责边界**  
软件元数据：列表、详情、分类筛选、上下架状态只读（变更归 Admin）。版本细节归 AppVersions。

**路由前缀**：`/api/v1/apps`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| GET | `/apps` | 软件列表 | 游客/登录 | MVP |
| GET | `/apps/{id}` | 软件详情 | 游客/登录 | MVP |
| GET | `/apps/categories` | 分类列表 | 游客/登录 | MVP |
| GET | `/apps/{id}/related-posts` | 关联讨论帖 | 游客/登录 | MVP |
| GET | `/apps/{id}/rating` | 评分汇总 | 游客/登录 | 二期 |
| POST | `/apps/{id}/rating` | 用户评分 | 登录 | 二期 |

**关键字段要点**

- 列表/详情：`id`、`name`、`slug`、`icon`、`summary`、`description`、`category`、`latest_version`（嵌套摘要）、`download_count`、`status`。
- 筛选：`category_id`、`keyword`、`sort=download|new`。

**依赖**：AppVersions（最新版摘要）；Posts（关联）  
**禁止**：写版本包文件/下载凭证（归 AppVersions/Downloads）；前台接口不得改 `status`。

---

### 3.8 AppVersions · 软件版本

**职责边界**  
某软件的版本列表、版本详情、 changelog；上传/发布走 Admin。前台只读已发布版本。

**路由前缀**：`/api/v1/app-versions` 与 `/api/v1/apps/{app_id}/versions`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| GET | `/apps/{app_id}/versions` | 版本列表 | 游客/登录 | MVP |
| GET | `/app-versions/{id}` | 版本详情 | 游客/登录 | MVP |
| GET | `/apps/{app_id}/versions/latest` | 最新正式版 | 游客/登录 | MVP |
| GET | `/app-versions/{id}/changelog` | 更新说明 | 游客/登录 | 二期 |

**关键字段要点**

- `id`、`app_id`、`version_name`、`version_code`、`channel`、`size`、`checksum`、`min_os`、`released_at`、`changelog` 摘要。
- 不直接返回可盗链的永久下载 URL；下载走 Downloads 签发。

**依赖**：Apps  
**禁止**：前台写版本；禁止在本模块完成计费扣次（若有，归 Downloads/订单二期）。

---

### 3.9 Downloads · 下载

**职责边界**  
创建下载任务/签发短时 URL、上报完成、我的下载记录。做频控与权限（登录/过审软件）。

**路由前缀**：`/api/v1/downloads`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| POST | `/downloads` | 申请下载（版本 id） | 登录（MVP；或按产品放游客） | MVP |
| GET | `/downloads/{id}` | 下载单状态/链接 | 本人 | MVP |
| POST | `/downloads/{id}/complete` | 客户端上报完成 | 本人 | MVP |
| GET | `/downloads/mine` | 我的下载历史 | 本人 | MVP |
| GET | `/downloads/quota` | 今日配额 | 本人 | 二期 |

**关键字段要点**

- 申请：`app_version_id`、`client`（ios/android/h5）。
- 响应：`download_id`、`url`（短时签名）、`expires_at`、`headers`（可选校验头）。
- 完成：`bytes`、`duration_ms`、`status=success|fail`。

**依赖**：AppVersions、Apps、Auth  
**禁止**：直接改 `app_versions` 文件字段；计数递增通过本模块服务写 `apps.download_count` 或发事件（推荐事件，避免双写混乱）。

---

### 3.10 Ops · 运营位（Banner / 九宫格）

**职责边界**  
首页 Banner、九宫格入口等只读配置下发。配置 CRUD 归 Admin。

**路由前缀**：`/api/v1/ops`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| GET | `/ops/banners` | Banner 列表 | 游客/登录 | MVP |
| GET | `/ops/grid` | 九宫格入口 | 游客/登录 | MVP |
| GET | `/ops/home-layout` | 首页区块编排 | 游客/登录 | 二期 |
| GET | `/ops/popups` | 弹窗运营 | 登录/游客 | 二期 |

**关键字段要点**

- Banner：`id`、`image`、`link_type`（`url|app|post|board`）、`link_value`、`sort`、`start_at`、`end_at`。
- Grid：`id`、`title`、`icon`、`link_type`、`link_value`、`badge`。

**依赖**：无强依赖；链接解析前端处理  
**禁止**：前台写运营数据；不在此接口返回用户隐私。

---

### 3.11 Reports · 举报

**职责边界**  
用户提交举报、查看自己的举报状态。处理归 Admin。

**路由前缀**：`/api/v1/reports`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| POST | `/reports` | 提交举报 | 登录 | MVP |
| GET | `/reports/mine` | 我的举报列表 | 本人 | MVP |
| GET | `/reports/{id}` | 单条进度 | 本人 | 二期 |

**关键字段要点**

- 写：`target_type`（`post|comment|user|app`）、`target_id`、`reason_code`、`detail`、`images[]`。
- 读：`status`（`pending|processing|resolved|rejected`）、`created_at`。

**依赖**：目标模块存在性校验（只读）  
**禁止**：举报接口直接隐藏帖子/封号（必须经 Admin 工单）。

---

### 3.12 Notifications · 通知

**职责边界**  
站内信列表、已读、未读数。推送通道二期。

**路由前缀**：`/api/v1/notifications`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| GET | `/notifications` | 通知列表 | 本人 | MVP |
| GET | `/notifications/unread-count` | 未读数 | 本人 | MVP |
| POST | `/notifications/{id}/read` | 单条已读 | 本人 | MVP |
| POST | `/notifications/read-all` | 全部已读 | 本人 | MVP |
| POST | `/notifications/device` | 登记推送设备 | 本人 | 二期 |

**关键字段要点**

- 项：`id`、`type`、`title`、`body`、`payload`（跳转用）、`read_at`、`created_at`。
- 生产方：其他模块发领域事件 → 本模块写入；禁止 Posts 控制器直接 insert 通知表以外的乱写。

**依赖**：Auth；消费各模块事件  
**禁止**：改业务主数据（帖/评论状态）。

---

### 3.13 Admin · 后台

**职责边界**  
审核、上下架、运营配置、用户封禁、举报处理。按子模块拆路由，**仍只加本子模块文件**，不改前台契约。

**路由前缀**：`/api/v1/admin`  
鉴权：管理员 JWT + 权限点。

**子模块与前缀**

| 子模块 | 前缀 | MVP 能力 |
|---|---|---|
| 认证会话 | `/admin/auth` | 管理员登录/注销 |
| 用户 | `/admin/users` | 列表、封禁/解封、角色 |
| 版块 | `/admin/boards` | CRUD、排序 |
| 帖子审核 | `/admin/posts` | 列表、编辑、通过/拒绝、删帖 |
| 评论 | `/admin/comments` | 删评、屏蔽 |
| 软件 | `/admin/apps` | CRUD、上下架 |
| 版本 | `/admin/app-versions` | 上传元数据、发布/下线 |
| 运营 | `/admin/ops` | Banner/九宫格 CRUD |
| 举报 | `/admin/reports` | 处理工单 |
| 仪表（二期） | `/admin/stats` | 概览指标 |

**接口表（MVP 核心，可继续按子模块文件追加）**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| POST | `/admin/auth/login` | 管理员登录 | 管理员账号 | MVP |
| GET | `/admin/users` | 用户分页 | 用户管理权限 | MVP |
| POST | `/admin/users/{id}/ban` | 封禁 | 用户管理权限 | MVP |
| POST | `/admin/users/{id}/unban` | 解封 | 用户管理权限 | MVP |
| GET/POST/PATCH/DELETE | `/admin/boards`… | 版块维护 | 内容权限 | MVP |
| GET | `/admin/posts` | 待审/全部帖 | 内容权限 | MVP |
| GET | `/admin/posts/{id}` | 审核详情（含正文、封面） | 内容权限 | MVP |
| PUT/PATCH | `/admin/posts/{id}` | 编辑标题、正文、版块、封面。正文是 HTML 字符串（富文本）。不接受 `status`，状态只走通过/拒绝。入库时去掉 `script` 等标签 | 内容权限 | MVP |
| POST | `/admin/posts/{id}/approve` | 过审 | 内容权限 | MVP |
| POST | `/admin/posts/{id}/reject` | 拒审 | 内容权限 | MVP |
| GET/POST/PATCH | `/admin/apps`… | 软件维护 | 商店权限 | MVP |
| GET/POST/PATCH | `/admin/app-versions`… | 版本发布 | 商店权限 | MVP |
| GET/POST/PATCH/DELETE | `/admin/ops/banners`… | Banner | 运营权限 | MVP |
| GET/POST/PATCH/DELETE | `/admin/ops/grid`… | 九宫格 | 运营权限 | MVP |
| GET | `/admin/reports` | 举报队列 | 治理权限 | MVP |
| POST | `/admin/reports/{id}/resolve` | 处理完成 | 治理权限 | MVP |
| GET | `/admin/stats/overview` | 数据概览 | 报表权限 | 二期 |

**关键字段要点**

- 审核：`reason`、`operator_id`、`operated_at`。
- 版本发布：`storage_key`、`checksum`、`version_code` 唯一约束（同 app）。

**依赖**：各业务模块的**管理侧 Application Service**（推荐），而非复制一套表操作散落在 Controller。  
**禁止**：Admin 控制器直接 `DB::table` 跨十张表裸写；禁止前台路由复用 Admin 控制器。

---

### 3.14 System · 系统

**职责边界**  
客户端启动配置、协议文案、健康检查、客户端版本门禁。不承载业务 CRUD。

**路由前缀**：`/api/v1/system`

**接口表**

| Method | Path | 简述 | 谁可调 | 阶段 |
|---|---|---|---|---|
| GET | `/system/config` | 公开配置（开关、审核模式文案键） | 游客/登录 | MVP |
| GET | `/system/agreements/{type}` | 用户协议/隐私政策 | 游客 | MVP |
| GET | `/system/client-version` | H5/App 最小版本与更新提示 | 游客 | MVP |
| GET | `/system/health` | 存活探测 | 内网/监控 | MVP |
| GET | `/system/time` | 服务器时间 | 游客 | 二期 |

**关键字段要点**

- `config`：`site_name`、`register_enabled`、`force_login_download`、`upload_max_mb` 等扁平键；敏感键不下发。
- `client-version`：`min_version`、`latest_version`、`force_update`、`store_url`。

**依赖**：无  
**禁止**：在 config 接口塞用户维度数据；禁止业务模块把「临时开关」写进全局中间件而不登记本表。

---

## 4. MVP vs 二期（汇总口径）

- **MVP**：能完成「注册登录 → 逛版块/帖子/评论/赞藏 → 逛软件/版本 → 申请下载 → 看运营位 → 举报 → 收通知 → 后台基础审核与运营配置」。
- **二期**：第三方登录与找回密码、关注、置顶锁帖、评分、下载配额、推送、首页编排、Admin 报表、表情回应等。
- 开发文档落地后：以开发文档的范围表为准，**冲突时改本规划对应模块行**，不改 §1 全局契约。

**MVP 接口大约数量（按上表勾选粗算）**：约 **55～65** 条（含 Admin 子资源 CRUD 按「一组」计入后的常用动作；若 Admin 每个 Method 拆计可到约 **70+**）。  
**二期**：约 **20～25** 条预留。

---

## 5. 扩展规则

### 5.1 新增 API 检查清单

提交前逐项打勾：

1. **归属**：能明确唯一模块；新文件进该模块路由，不进 `api.php` 大锅烩（最多一行 `require`）。
2. **命名**：资源复数、嵌套合理、无动词堆查询串。
3. **版本**：仍挂 `/api/v1`；确认非破坏性。
4. **鉴权**：公开 / 登录 / 权限点三者之一写清；Policy 单测或清单注明。
5. **响应**：遵守 `{code,message,data}`；错误码用 §1.4，不新造壳。
6. **分页/排序**：列表必须符合 §1.6；排序字段白名单。
7. **跨模块**：只读校验或调他模块 Service；**不直接写他表**。
8. **文档**：更新本文件该模块接口表 + 下方 Changelog；标明 MVP/二期。
9. **兼容**：旧字段不删不改义；新增字段用可选。
10. **观测**：关键写路径有日志字段（user_id、target、latency），但不在规划阶段强制实现细节。

### 5.2 何时升 `/api/v2`

满足任一即评估升版（通常并行保留 v1 至少一个发布周期）：

- 统一响应壳变更；
- 鉴权方案变更（如 JWT → Session/Cookie 混用且不兼容）；
- 分页语义变更（页码 → 强制游标且旧客户端不可用）；
- 资源路径大规模重命名；
- 错误码含义复用冲突无法追加解决。

仅「多几个接口 / 多几个可选字段」→ **不升版**，只改模块节。

### 5.3 Changelog（文档级）

| 日期 | 版本 | 说明 |
|---|---|---|
| 2026-10-21 | 0.1 | 首版 API 规划：14 模块、原则与扩展规则；作者 软件库 |
| 2026-10-21 | 0.1.1 | 落入仓库 `docs/api-planning.md`；对齐路径改为 `docs/development.md`。全局契约未改 |
| 2026-10-21 | 0.1.2 | Admin 帖子增加 `PUT/PATCH /admin/posts/{id}`：改标题、正文、版块、封面。状态仍只由通过/拒绝改变。响应壳未改 |
| 2026-10-21 | 0.1.3 | 管理端正文约定为 HTML，列表摘要仍 `strip_tags`。保存时去掉 script/iframe 和 `javascript:` 链接。响应壳未改 |
| 2026-10-22 | 0.1.4 | 未生成安装锁时，`/api/v1` 返回 `50301`，`data.install_url` 指向 `/install`。响应壳未改 |

---

## 6. 目录与路由落位建议（便于「按模块开发」）

```
routes/api/v1/
  auth.php
  users.php
  boards.php
  posts.php
  comments.php
  interactions.php
  apps.php
  app_versions.php
  downloads.php
  ops.php
  reports.php
  notifications.php
  system.php
  admin/
    auth.php
    users.php
    boards.php
    posts.php
    ...
routes/api.php          # 仅 require 上述文件 + 前缀中间件，不写具体业务路由
```

控制器、FormRequest、Policy、API Resource 均按模块分目录。全局仅保留：JSON 响应宏、JWT 中间件、限流、异常转码——**契约变更走 RFC/评审，不随单个需求改**。

---

## 7. 维护约定摘要（给研发）

1. **全局契约冻结**：响应壳、错误码表结构、JWT 头、分页参数名 —— 变更极少且要评审。  
2. **新接口只进本模块**：路由、控制器、Policy、文档小节同步改。  
3. **禁止跨模块直接改他表**：用 Service / 领域事件。  
4. **不怕接口变多，怕边界糊**：先定模块再写 Path。  
5. **Changelog 必写**：否则视为未完成。

---

*本文档为规划真源之一；实现以代码为准时，须回写接口表状态（可另加「已实现」列，不在 0.1 强制）。*
