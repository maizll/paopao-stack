# SaaS 补充约定

| 项 | 内容 |
|---|---|
| 文档版本 | 0.1 |
| 日期 | 2026-10-21 |
| 产品 | 泡泡栈 |

本文件是附件中的 SaaS 补充约定落入仓库后的正文，供研发与文档互相引用。

- 多租户：`tenants` 表；业务表一律带 `tenant_id`；请求经域名/子域或 `X-Tenant-Id` 解析租户。
- 平台超管 vs 租户管理员分离；套餐/开通/配额预留表，MVP 可写死套餐。
- 文档进仓库 `docs/`：开发文档、API 规划、本补充。
- 栈：Laravel（API）+ Vue3 H5（用户端）+ Vue3 Admin（后台）；按模块路由，契约见 API 规划。
- MVP：租户开通占位、账号、圈子图文+审核、软件上架下载+审核、Banner/九宫格、举报、后台；商城/支付/私信/短视频二期。

## 本轮骨架如何落地

| 约定 | 代码位置 |
|---|---|
| `tenants` 与 `plans` | `backend/database/migrations/2026_10_21_150001_create_plans_and_tenants_tables.php` |
| 业务表 `tenant_id` | 版块、帖子、软件、版本、运营位；`BelongsToTenant` 全局范围 |
| 解析顺序 | `X-Tenant-Id` → 完整域名 → 子域 → 仅本地的 `TENANT_DEFAULT_SLUG` |
| 平台超管 | `users.role = platform_super_admin`，`tenant_id` 为空 |
| 租户管理员 | `users.role = tenant_admin`，必须属于当前租户 |
| 套餐 | `plans` 表预留配额 JSON；种子只写 `free`，不做强制扣减 |
| 二期不做 | 商城、支付、私信、短视频不注册路由。API 规划中的二期动作返回 `42201` |

账号在 MVP 全局唯一（邮箱或手机号），用户行仍带 `tenant_id`。`users` 不挂全局租户范围，避免登录和平台超管被过滤掉；租户内查询由调用方显式带 `tenant_id`。
