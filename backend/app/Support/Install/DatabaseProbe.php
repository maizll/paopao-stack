<?php

namespace App\Support\Install;

use PDO;
use PDOException;

/**
 * 用表单里的账号直接探测 MySQL / MariaDB，不经过 Laravel 的默认连接。
 */
final class DatabaseProbe
{
    /**
     * @param  array{host: string, port: int, database: string, username: string, password: ?string}  $config
     * @return array{ok: bool, message: string, server?: string, created?: bool}
     */
    public static function test(array $config): array
    {
        try {
            $pdo = self::connect($config, true);
            $server = (string) $pdo->query('SELECT VERSION()')->fetchColumn();

            return [
                'ok' => true,
                'created' => false,
                'server' => $server,
                'message' => '已连接到 '.$config['database'].'（'.$server.'）。',
            ];
        } catch (PDOException $exception) {
            $code = (int) ($exception->errorInfo[1] ?? 0);
            if ($code !== 1049) {
                return ['ok' => false, 'message' => self::explain($exception)];
            }
        }

        try {
            $serverPdo = self::connect($config, false);
            $name = str_replace('`', '', $config['database']);
            $serverPdo->exec(
                'CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            );
            $pdo = self::connect($config, true);
            $server = (string) $pdo->query('SELECT VERSION()')->fetchColumn();

            return [
                'ok' => true,
                'created' => true,
                'server' => $server,
                'message' => '数据库 '.$config['database'].' 原先不存在，已创建并连接（'.$server.'）。',
            ];
        } catch (PDOException $exception) {
            $code = (int) ($exception->errorInfo[1] ?? 0);
            if ($code === 1049) {
                return ['ok' => false, 'message' => '数据库不存在，且当前账号没有建库权限。'];
            }

            return ['ok' => false, 'message' => self::explain($exception)];
        }
    }

    /**
     * @param  array{host: string, port: int, database: string, username: string, password: ?string}  $config
     */
    private static function connect(array $config, bool $withDatabase): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $config['host'], $config['port']);
        if ($withDatabase) {
            $dsn .= ';dbname='.$config['database'];
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ];
        if (defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')) {
            $options[PDO::MYSQL_ATTR_CONNECT_TIMEOUT] = 5;
        }

        return new PDO($dsn, $config['username'], (string) ($config['password'] ?? ''), $options);
    }

    private static function explain(PDOException $exception): string
    {
        $code = (int) ($exception->errorInfo[1] ?? 0);

        return match ($code) {
            1044 => '这个账号没有访问该数据库的权限。',
            1045 => '数据库账号或密码不正确。',
            2002, 2003 => '连不上数据库主机，请检查地址、端口和数据库是否已启动。',
            default => '数据库连接失败：'.$exception->getMessage(),
        };
    }
}
