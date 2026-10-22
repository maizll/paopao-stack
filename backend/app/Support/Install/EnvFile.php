<?php

namespace App\Support\Install;

/**
 * 只改动指定键，保留 .env 里的其余配置。
 */
final class EnvFile
{
    public static function path(): string
    {
        return base_path('.env');
    }

    /**
     * 内容没有变化时不写文件，避免 php artisan serve 因 .env 改动重启并打断本次请求。
     *
     * @param  array<string, string|int|null>  $values
     */
    public static function put(array $values): bool
    {
        $path = self::path();
        if (! is_file($path)) {
            $example = base_path('.env.example');
            if (! is_file($example) || ! copy($example, $path)) {
                throw new \RuntimeException('找不到 .env.example，无法创建 .env。');
            }
        }

        $contents = (string) file_get_contents($path);
        $original = $contents;
        foreach ($values as $key => $value) {
            $line = $key.'='.self::escape($value === null ? '' : (string) $value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            if (preg_match($pattern, $contents) === 1) {
                $contents = (string) preg_replace($pattern, $line, $contents, 1);
            } else {
                $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
            }
        }

        if ($contents === $original) {
            return false;
        }

        $temporary = $path.'.installing';
        if (file_put_contents($temporary, $contents) === false || ! rename($temporary, $path)) {
            throw new \RuntimeException('无法写入 .env。');
        }

        return true;
    }

    public static function get(string $key): string
    {
        $path = self::path();
        if (! is_file($path)) {
            return '';
        }

        $contents = (string) file_get_contents($path);
        if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches) !== 1) {
            return '';
        }

        return trim($matches[1], " \t\"'");
    }

    private static function escape(string $value): string
    {
        if ($value === '' || preg_match('/[\s#"\'\\\\$]/', $value) === 1) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }
}
