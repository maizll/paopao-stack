<?php

namespace App\Http\Middleware;

use App\Support\ApiCode;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use App\Support\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 登录用户必须属于当前租户。平台超管例外。
 */
class EnsureUserTenant
{
    public function handle(Request $request, Closure $next): Response
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

        $tenantId = app(TenantContext::class)->id();

        if (! $tenantId || $user->tenant_id !== $tenantId) {
            return ApiResponse::fail(ApiCode::FORBIDDEN, '登录态与当前租户不一致', 403);
        }

        return $next($request);
    }
}
