#!/bin/sh
set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
  echo "缺少 APP_KEY。请填 php artisan key:generate --show 的结果。" >&2
  exit 1
fi

# Render Postgres 注入 DATABASE_URL。Laravel 读 DB_URL。
if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ]; then
  export DB_URL="$DATABASE_URL"
fi

attempt=0
until php artisan migrate --force; do
  attempt=$((attempt + 1))
  if [ "$attempt" -ge 30 ]; then
    echo "数据库在 30 次尝试后仍无法迁移。" >&2
    exit 1
  fi
  echo "数据库还没就绪，第 ${attempt} 次重试。"
  sleep 2
done

seed="${PAOPAO_SEED:-true}"
if [ "$seed" = "true" ] || [ "$seed" = "1" ]; then
  php artisan db:seed --force
fi

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch storage/app/install.lock

if [ ! -e public/storage ]; then
  php artisan storage:link
fi

port="${PORT:-10000}"
exec php artisan serve --host=0.0.0.0 --port="$port"
