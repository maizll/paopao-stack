# 泡泡栈文档

| 文档 | 说明 |
|---|---|
| [开发文档](development.md) | 产品范围、仓库结构、多租户、角色、本轮做到哪 |
| [API 规划](api-planning.md) | 全局契约与 14 个模块的接口表。响应壳、错误码、分页以本文为准 |
| [SaaS 补充约定](saas-conventions.md) | 租户、平台超管 / 租户管理员、套餐预留、二期边界 |

本地启动见仓库根目录 [README](../README.md)。用户端是 `apps/uni`（uni-app + uni-ui）。运营后台是 `apps/admin` 里的 Vue Vben Admin 5.7.0 `@vben/web-antd`。

改接口时：先改对应模块路由和控制器，再改 `api-planning.md` 的模块表和 Changelog。不要改 §1 的响应壳。
