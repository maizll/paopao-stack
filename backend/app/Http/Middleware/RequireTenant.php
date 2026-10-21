<?php

namespace App\Http\Middleware;

use App\Support\ApiCode;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(TenantContext::class)->get()) {
            return ApiResponse::fail(
                ApiCode::VALIDATION,
                '缺少租户：请设置请求头 X-Tenant-Id 或使用租户域名',
                400,
            );
        }

        return $next($request);
    }
}
