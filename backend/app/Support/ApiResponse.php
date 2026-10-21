<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * 统一响应壳：{ code, message, data }。
 * 模块只能调用这里，不能另造 success / errno 等字段。
 */
final class ApiResponse
{
    public static function ok(mixed $data = null, string $message = 'ok'): JsonResponse
    {
        return self::envelope(ApiCode::OK, $message, $data, 200);
    }

    public static function fail(int $code, string $message, int $http, mixed $data = null): JsonResponse
    {
        return self::envelope($code, $message, $data, $http);
    }

    private static function envelope(int $code, string $message, mixed $data, int $http): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $http);
    }
}
