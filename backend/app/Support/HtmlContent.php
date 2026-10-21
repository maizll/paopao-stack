<?php

namespace App\Support;

/**
 * 运营后台富文本入库前的最小清理。保留标题、段落、列表、链接和图片，去掉脚本。
 */
final class HtmlContent
{
    public static function sanitize(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*\/?>#is', '', $html) ?? $html;
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/\s(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/i', '', $html) ?? $html;

        return trim($html);
    }
}
