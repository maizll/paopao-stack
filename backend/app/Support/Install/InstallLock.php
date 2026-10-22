<?php

namespace App\Support\Install;

/**
 * 安装完成后的锁。文件在 storage/app/install.lock，不进仓库。
 */
final class InstallLock
{
    public static function path(): string
    {
        return (string) config('paopao.install_lock');
    }

    public static function exists(): bool
    {
        return is_file(self::path());
    }

    /**
     * @return array<string, mixed>
     */
    public static function read(): array
    {
        if (! self::exists()) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents(self::path()), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function write(array $payload): void
    {
        $path = self::path();
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false || file_put_contents($path, $json.PHP_EOL) === false) {
            throw new \RuntimeException('无法写入安装锁：'.$path);
        }
    }
}
