<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class ListQuery
{
    public static function page(Request $request): int
    {
        return max(1, (int) $request->query('page', 1));
    }

    public static function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 20);

        if ($perPage > 50) {
            Log::info('per_page capped', [
                'requested' => $perPage,
                'cap' => 50,
                'path' => $request->path(),
            ]);

            return 50;
        }

        return max(1, $perPage);
    }

    /**
     * @param  list<string>  $whitelist
     */
    public static function sort(Request $request, array $whitelist, string $default): string
    {
        $sort = (string) $request->query('sort', $default);

        return in_array($sort, $whitelist, true) ? $sort : $default;
    }

    public static function order(Request $request, string $default = 'desc'): string
    {
        $order = strtolower((string) $request->query('order', $default));

        return in_array($order, ['asc', 'desc'], true) ? $order : $default;
    }
}
