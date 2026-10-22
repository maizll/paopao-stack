# 在 Render 上部署 API

正式公网地址用 Render 的固定 HTTPS 域名，例如 `https://paopao-api.onrender.com`。不要把 Cloudflare Quick Tunnel 当作最终地址：隧道进程一停，域名就换，已经装上的 APK 会连不上。

用户端只认一个字段：`apps/uni/src/config.ts` 的 `API_BASE_URL`。Render 给出域名后，只改这一行，再打包 APK。

Render 从 GitHub 或 GitLab 拉代码。本仓库现在在 Origin 上，要等代码推到 GitHub 私有仓库之后，再在 Render 里连接那个仓库。

## 服务怎么建

仓库根目录有 `render.yaml`。在 Render 里 New → Blueprint，选这个文件，会创建：

- Web Service `paopao-api`（Docker，免费档）
- Postgres `paopao-db`（免费档）

也可以手工建 Web Service，设置与 Blueprint 相同：

| 项 | 值 |
|---|---|
| Runtime | Docker |
| Root Directory | 留空（仓库根） |
| Dockerfile Path | `backend/Dockerfile` |
| Docker Context | `backend` |
| Health Check Path | `/up` |
| Start | 镜像自带，不用另填。入口是 `backend/docker/entrypoint.sh` |

启动顺序：

1. `php artisan migrate --force`（数据库还没就绪时最多重试 30 次）
2. `PAOPAO_SEED` 为 `true` 时执行 `php artisan db:seed --force`，写入演示租户和账号
3. 写入 `storage/app/install.lock`，API 不再被安装向导拦住
4. `php artisan storage:link`
5. `php artisan serve --host 0.0.0.0 --port $PORT`

`$PORT` 由 Render 注入。健康检查有两条：

- `GET /up`：进程起来就返回 200，给 Render 用
- `GET /api/v1/system/health`：检查数据库，返回 `{"code":0,"data":{"status":"ok"}}`

成员登录：`POST /api/v1/auth/login`，请求头 `X-Tenant-Id: demo`，正文 `{"account":"member@demo.paopao.local","password":"password"}`。

`*.onrender.com` 不会被当成租户子域，请求落到 `TENANT_DEFAULT_SLUG`。

## 环境变量

必填：

| 变量 | 值 |
|---|---|
| `APP_KEY` | 本机在 `backend/` 执行 `php artisan key:generate --show`，把整行（含 `base64:`）贴进 Render。不要每次部署重新生成，否则已发出的登录态会失效 |
| `APP_URL` | Render 分配的地址，例如 `https://paopao-api.onrender.com`，不要带末尾斜杠。图片 URL 用它拼出来 |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `DB_CONNECTION` | `pgsql`。免费档用 Render Postgres。迁移是 Laravel 写法，不绑 MySQL |
| `DATABASE_URL` | 从关联的 Postgres 选 Internal Connection String。入口脚本会把它交给 Laravel 的 `DB_URL`。也可以不填 `DATABASE_URL`，改填 `DB_HOST`、`DB_PORT`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD` |
| `DB_SSLMODE` | 同区域内部连接用 `prefer`。若报 SSL 错误，改成 `require` |
| `TENANT_DEFAULT_SLUG` | `demo`。手机不带租户域名时用这个租户 |

建议同时设置：

| 变量 | 值 | 原因 |
|---|---|---|
| `LOG_CHANNEL` | `stderr` | 日志进 Render 控制台 |
| `SESSION_DRIVER` | `database` | 会话表在默认迁移里 |
| `CACHE_STORE` | `database` | 不依赖 Redis |
| `QUEUE_CONNECTION` | `sync` | 没有单独的 worker，队列当场执行 |
| `FILESYSTEM_DISK` | `local` | 上传接口自己写 `public` 盘，经 `/storage` 访问 |
| `PAOPAO_SEED` | `true` | 每次启动用 `updateOrCreate` 保证演示账号还在。改成 `false` 就不再重写演示数据 |
| `SANCTUM_STATEFUL_DOMAINS` | 留空 | 用户端用 Bearer Token，不走 Cookie。框架默认 CORS 对 `api/*` 允许任意来源，且 `supports_credentials` 为 false |

不要把 `backend/.env` 提交进 GitHub。生产密钥只放在 Render 的 Environment。

## 演示账号

租户 `demo`。

- 成员 `member@demo.paopao.local` / `password`
- 管理员 `admin@demo.paopao.local` / `password`

## 换到这个地址之后

1. 确认 `curl https://<render域名>/up` 和 `curl https://<render域名>/api/v1/system/health` 都是 200。
2. 只改 `apps/uni/src/config.ts` 的 `API_BASE_URL` 为这个域名。
3. 重新打包 APK。旧包里的隧道地址不会跟着变。

## 限制

- 免费 Web Service 一段时间没人访问会休眠，第一次请求要等它醒来。
- 容器磁盘会在重新部署时清空。封面和上传的图片若没有挂载持久磁盘，会丢。演示帖的文字在数据库里，不受影响。
- 免费 Postgres 到期或被删后，要重新 migrate 和 seed。
- 这只部署 API。管理后台和用户端 H5 不在这个服务里。
