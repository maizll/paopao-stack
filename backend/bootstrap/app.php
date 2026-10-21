<?php

use App\Exceptions\ApiExceptionMapper;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserTenant;
use App\Http\Middleware\OptionalSanctum;
use App\Http\Middleware\RequireTenant;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ResolveTenant::class,
        ]);

        $middleware->alias([
            'tenant.required' => RequireTenant::class,
            'tenant.user' => EnsureUserTenant::class,
            'role' => EnsureRole::class,
            'auth.optional' => OptionalSanctum::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        ApiExceptionMapper::register($exceptions);
    })->create();
