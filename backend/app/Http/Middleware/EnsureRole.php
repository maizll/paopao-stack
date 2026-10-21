<?php

namespace App\Http\Middleware;

use App\Support\ApiCode;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use App\Support\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::fail(ApiCode::UNAUTHENTICATED, '未登录或 Token 无效', 401);
        }

        if ($user->status !== 'active') {
            return ApiResponse::fail(ApiCode::FORBIDDEN, '账号已封禁', 403);
        }

        if ($user->role === UserRole::PLATFORM_SUPER_ADMIN) {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            return ApiResponse::fail(ApiCode::FORBIDDEN, '无权限', 403);
        }

        if ($user->role === UserRole::TENANT_ADMIN && $user->tenant_id !== app(TenantContext::class)->id()) {
            return ApiResponse::fail(ApiCode::FORBIDDEN, '不能管理其他租户', 403);
        }

        return $next($request);
    }
}
