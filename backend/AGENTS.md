# 泡泡栈 API

这是泡泡栈的 Laravel API，不是一个待初始化的空 Laravel 项目。

- 契约与模块边界：仓库 `docs/`
- 本地启动：仓库根目录 `README.md`
- 新接口只加进 `routes/api/v1/{module}.php`，响应壳保持 `{code, message, data}`
- 不要实现商城、支付、私信、短视频；那些是二期
