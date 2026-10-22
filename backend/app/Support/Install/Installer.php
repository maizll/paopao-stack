<?php

namespace App\Support\Install;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 写配置、迁移、创建平台超管和首个租户。演示数据可选。
 */
final class Installer
{
    /**
     * @param  array{host?: string, port?: int, database: string, username?: string, password?: ?string, driver?: string}  $database
     * @param  array{email: string, password: string, nickname: string}  $admin
     * @param  array{site_name: string, app_url: string, tenant_name: string, tenant_slug: string, seed_demo: bool}  $site
     * @return array{log: list<string>}
     */
    public function run(array $database, array $admin, array $site, bool $persistEnv = true): array
    {
        $log = [];
        $previousDefault = (string) config('database.default');

        $this->activateConnection($database);
        $log[] = '已切换到安装使用的数据库连接。';

        try {
            $migrateCode = Artisan::call('migrate', ['--force' => true]);
            if ($migrateCode !== 0) {
                throw new RuntimeException(trim(Artisan::output()) ?: '数据表迁移失败。');
            }
            $log[] = '数据表迁移完成，当前 '.$this->tableCount().' 张表。';

            $this->ensurePlanAndTenant($site);
            $log[] = '已写入租户「'.$site['tenant_name'].'」（'.$site['tenant_slug'].'）。';

            if ($site['seed_demo']) {
                $seedCode = Artisan::call('db:seed', ['--force' => true]);
                if ($seedCode !== 0) {
                    throw new RuntimeException(trim(Artisan::output()) ?: '演示数据写入失败。');
                }
                $log[] = '已写入演示数据：示例成员、版块、帖子和软件。';
            } else {
                $log[] = '已跳过演示数据。';
            }

            User::query()->updateOrCreate(
                ['email' => $admin['email']],
                [
                    'tenant_id' => null,
                    'name' => $admin['nickname'],
                    'nickname' => $admin['nickname'],
                    'password' => $admin['password'],
                    'role' => UserRole::PLATFORM_SUPER_ADMIN,
                    'status' => 'active',
                ],
            );
            $log[] = '已创建平台超管 '.$admin['email'].'。';

            $payload = [
                'installed_at' => now()->toIso8601String(),
                'site_name' => $site['site_name'],
                'app_url' => $site['app_url'],
                'admin_email' => $admin['email'],
                'tenant_slug' => $site['tenant_slug'],
                'tenant_name' => $site['tenant_name'],
                'seed_demo' => $site['seed_demo'],
                'log' => $log,
            ];
            if ($persistEnv) {
                $changed = $this->writeEnv($database, $site, $log);
                $log[] = $changed
                    ? '已写入 .env。'
                    : '环境配置与现有 .env 一致，未改文件。';
            }

            $payload['log'] = $log;
            InstallLock::write($payload);
            $log[] = '已写入安装锁，不能再次安装。';
            $payload['log'] = $log;
            InstallLock::write($payload);

            return ['log' => $log];
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException && $exception->getPrevious() === null) {
                throw $exception;
            }

            $message = $exception->getMessage();
            if ($message === '') {
                $message = '安装没有完成。';
            }

            throw new RuntimeException($message, 0, $exception);
        } finally {
            config(['database.default' => $previousDefault]);
        }
    }

    /**
     * @param  array{host?: string, port?: int, database: string, username?: string, password?: ?string, driver?: string}  $database
     * @param  array{site_name: string, app_url: string, tenant_slug: string}  $site
     * @param  list<string>  $log
     */
    private function writeEnv(array $database, array $site, array &$log): bool
    {
        foreach ([base_path('bootstrap/cache/config.php'), base_path('bootstrap/cache/routes-v7.php')] as $cached) {
            if (is_file($cached)) {
                unlink($cached);
            }
        }

        $key = EnvFile::get('APP_KEY');
        if ($key === '') {
            $key = 'base64:'.base64_encode(random_bytes(32));
            $log[] = '已生成应用密钥。';
        } else {
            $log[] = '已保留现有应用密钥。';
        }

        return EnvFile::put([
            'APP_NAME' => $site['site_name'],
            'APP_URL' => $site['app_url'],
            'APP_KEY' => $key,
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) ($database['host'] ?? '127.0.0.1'),
            'DB_PORT' => (string) ($database['port'] ?? 3306),
            'DB_DATABASE' => $database['database'],
            'DB_USERNAME' => (string) ($database['username'] ?? ''),
            'DB_PASSWORD' => (string) ($database['password'] ?? ''),
            'TENANT_DEFAULT_SLUG' => $site['tenant_slug'],
        ]);
    }

    /**
     * @param  array{host?: string, port?: int, database: string, username?: string, password?: ?string, driver?: string}  $database
     */
    private function activateConnection(array $database): void
    {
        if (($database['driver'] ?? 'mysql') === 'sqlite') {
            config([
                'database.connections.paopao_install' => [
                    'driver' => 'sqlite',
                    'database' => $database['database'],
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                ],
                'database.default' => 'paopao_install',
            ]);
            DB::purge('paopao_install');
            DB::reconnect('paopao_install');

            return;
        }

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $database['host'] ?? '127.0.0.1',
            'database.connections.mysql.port' => $database['port'] ?? 3306,
            'database.connections.mysql.database' => $database['database'],
            'database.connections.mysql.username' => $database['username'] ?? '',
            'database.connections.mysql.password' => $database['password'] ?? '',
        ]);
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    /**
     * @param  array{tenant_name: string, tenant_slug: string}  $site
     */
    private function ensurePlanAndTenant(array $site): void
    {
        Plan::query()->updateOrCreate(
            ['code' => 'free'],
            [
                'name' => '免费版',
                'is_default' => true,
                'quotas' => [
                    'boards' => 20,
                    'apps' => 50,
                    'admins' => 3,
                ],
            ],
        );

        Tenant::query()->updateOrCreate(
            ['slug' => $site['tenant_slug']],
            [
                'name' => $site['tenant_name'],
                'status' => 'active',
                'plan_code' => 'free',
            ],
        );
    }

    private function tableCount(): int
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return (int) DB::table('sqlite_master')->where('type', 'table')->count();
        }

        $rows = DB::select('SHOW TABLES');

        return count($rows);
    }
}
