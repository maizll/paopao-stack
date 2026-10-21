<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Http\Controllers\Api\V1\ApiController;
use App\Support\ApiCode;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemController extends ApiController
{
    public function health(): JsonResponse
    {
        $database = 'ok';

        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            $database = 'error';
        }

        return $this->ok([
            'status' => $database === 'ok' ? 'ok' : 'degraded',
            'app' => config('paopao.name'),
            'version' => config('paopao.version'),
            'database' => $database,
            'time' => now()->toIso8601String(),
        ]);
    }

    public function config(): JsonResponse
    {
        $tenant = app(TenantContext::class)->get();

        return $this->ok([
            'site_name' => config('paopao.name'),
            'register_enabled' => (bool) config('paopao.register_enabled'),
            'force_login_download' => (bool) config('paopao.force_login_download'),
            'upload_max_mb' => (int) config('paopao.upload_max_mb'),
            'tenant' => $tenant?->toPublicArray(),
        ]);
    }

    public function agreement(string $type): JsonResponse
    {
        $documents = [
            'terms' => [
                'type' => 'terms',
                'title' => '泡泡栈用户协议',
                'body' => '使用泡泡栈即表示你同意在所属租户内发布合法内容，并遵守版块与软件商店的审核规则。本骨架仅提供协议占位文案。',
            ],
            'privacy' => [
                'type' => 'privacy',
                'title' => '泡泡栈隐私政策',
                'body' => '账号、帖子与下载记录按租户隔离存储。平台超管仅在开通与治理场景访问租户数据。本骨架仅提供政策占位文案。',
            ],
        ];

        if (! isset($documents[$type])) {
            return ApiResponse::fail(ApiCode::NOT_FOUND, '资源不存在', 404);
        }

        return $this->ok($documents[$type]);
    }

    public function clientVersion(): JsonResponse
    {
        return $this->ok([
            'platform' => 'h5',
            'min_version' => '0.1.0',
            'latest_version' => '0.1.0',
            'force_update' => false,
            'store_url' => null,
        ]);
    }

    public function time(): JsonResponse
    {
        return $this->phaseTwo('服务器时间');
    }
}
