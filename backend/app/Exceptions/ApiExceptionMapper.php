<?php

namespace App\Exceptions;

use App\Support\ApiCode;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionMapper
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (ApiException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiResponse::fail($e->apiCode, $e->getMessage(), $e->httpStatus, $e->data);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiResponse::fail(ApiCode::VALIDATION, '参数校验失败', 400, [
                'errors' => $e->errors(),
            ]);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            // Sanctum 对无效与过期 token 都走未认证。40102 保留给后续可区分过期的守卫。
            return ApiResponse::fail(ApiCode::UNAUTHENTICATED, '未登录或 Token 无效', 401);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiResponse::fail(ApiCode::FORBIDDEN, '无权限', 403);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiResponse::fail(ApiCode::NOT_FOUND, '资源不存在', 404);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiResponse::fail(ApiCode::THROTTLED, '请求过于频繁', 429);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            $data = config('app.debug') ? [
                'exception' => $e::class,
                'detail' => $e->getMessage(),
            ] : null;

            return ApiResponse::fail(ApiCode::SERVER, '服务端错误', 500, $data);
        });
    }

    private static function isApi(Request $request): bool
    {
        return $request->is('api/*');
    }
}
