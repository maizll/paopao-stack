<?php

namespace App\Support\Install;

/**
 * 安装前环境检测。必过项失败会拦住下一步；Redis 只作可选提示。
 */
final class EnvironmentChecker
{
    /**
     * @return array{ready: bool, items: list<array{key: string, label: string, level: string, ok: bool, message: string}>}
     */
    public static function check(): array
    {
        $items = [
            self::phpVersion(),
            ...self::extensions(),
            self::writable('storage', storage_path(), 'storage'),
            self::writable('bootstrap', base_path('bootstrap/cache'), 'bootstrap/cache'),
            self::writable('env', self::envTarget(), '.env'),
            self::writable('lockdir', storage_path('app'), 'storage/app'),
            self::redis(),
        ];

        $ready = true;
        foreach ($items as $item) {
            if ($item['level'] === 'required' && ! $item['ok']) {
                $ready = false;
            }
        }

        return ['ready' => $ready, 'items' => $items];
    }

    /**
     * @return array{key: string, label: string, level: string, ok: bool, message: string}
     */
    private static function phpVersion(): array
    {
        $ok = PHP_VERSION_ID >= 80300;

        return [
            'key' => 'php',
            'label' => 'PHP 版本',
            'level' => 'required',
            'ok' => $ok,
            'message' => $ok
                ? '当前 '.PHP_VERSION.'，满足 8.3 及以上。'
                : '当前 '.PHP_VERSION.'，安装要求 PHP 8.3 及以上。请升级 PHP 后再检测。',
        ];
    }

    /**
     * @return list<array{key: string, label: string, level: string, ok: bool, message: string}>
     */
    private static function extensions(): array
    {
        $required = [
            'bcmath',
            'ctype',
            'curl',
            'dom',
            'fileinfo',
            'mbstring',
            'openssl',
            'pdo',
            'pdo_mysql',
            'tokenizer',
            'xml',
        ];

        $items = [];
        foreach ($required as $name) {
            $ok = extension_loaded($name);
            $items[] = [
                'key' => 'ext.'.$name,
                'label' => '扩展 '.$name,
                'level' => 'required',
                'ok' => $ok,
                'message' => $ok
                    ? '已启用。'
                    : '缺少扩展 '.$name.'。请在 php.ini 中启用后重新检测。',
            ];
        }

        return $items;
    }

    /**
     * @return array{key: string, label: string, level: string, ok: bool, message: string}
     */
    private static function writable(string $key, string $path, string $label): array
    {
        $ok = is_dir($path) ? is_writable($path) : (is_file($path) && is_writable($path));
        if ($key === 'env' && ! $ok && is_dir(base_path()) && is_writable(base_path())) {
            $ok = ! is_file($path);
        }

        return [
            'key' => 'dir.'.$key,
            'label' => '可写 '.$label,
            'level' => 'required',
            'ok' => $ok,
            'message' => $ok
                ? $path.' 可以写入。'
                : $path.' 不可写。请把该路径交给运行 PHP 的系统用户。',
        ];
    }

    private static function envTarget(): string
    {
        $env = base_path('.env');

        return is_file($env) ? $env : base_path();
    }

    /**
     * @return array{key: string, label: string, level: string, ok: bool, message: string}
     */
    private static function redis(): array
    {
        $errno = 0;
        $error = '';
        $socket = @stream_socket_client('tcp://127.0.0.1:6379', $errno, $error, 0.4);
        if (is_resource($socket)) {
            fclose($socket);

            return [
                'key' => 'redis',
                'label' => 'Redis（可选）',
                'level' => 'optional',
                'ok' => true,
                'message' => '127.0.0.1:6379 可以连接。安装不强制改用 Redis，缓存默认仍走数据库。',
            ];
        }

        $extension = extension_loaded('redis') ? '已安装 phpredis 扩展，但' : '未安装 phpredis 扩展，且';

        return [
            'key' => 'redis',
            'label' => 'Redis（可选）',
            'level' => 'optional',
            'ok' => false,
            'message' => $extension.'连不上 127.0.0.1:6379'.($error !== '' ? '（'.$error.'）' : '').'。不影响安装，缓存会使用数据库。',
        ];
    }
}
