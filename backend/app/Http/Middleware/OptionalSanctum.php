<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 公开读接口若带了 Bearer，则尝试识别用户；无效 token 仍按游客处理。
 */
class OptionalSanctum
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();

            if ($user) {
                Auth::shouldUse('sanctum');
                Auth::setUser($user);
            }
        }

        return $next($request);
    }
}
