<?php

namespace App\Http\Middleware;

use App\Support\ApiCode;
use App\Support\ApiResponse;
use App\Support\Install\InstallLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 没有安装锁时，浏览器进入向导，API 返回 50301。
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('install', 'install/*') || InstallLock::exists()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::fail(ApiCode::NOT_INSTALLED, '系统尚未安装', 503, [
                'install_url' => url('/install'),
            ]);
        }

        return redirect('/install');
    }
}
