<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\ApiCode;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 解析租户：X-Tenant-Id（slug 或数字 id）优先，其次域名 / 子域，最后才是本地默认 slug。
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);
        $context->forget();

        $explicit = trim((string) $request->header('X-Tenant-Id', ''));

        if ($explicit !== '') {
            $tenant = $this->findExplicit($explicit);
            if (! $tenant) {
                return ApiResponse::fail(ApiCode::NOT_FOUND, '租户不存在', 404);
            }
        } else {
            $tenant = $this->findFromHost($request);
            if ($tenant === false) {
                return ApiResponse::fail(ApiCode::NOT_FOUND, '租户不存在', 404);
            }
            if (! $tenant) {
                $slug = config('paopao.default_tenant_slug');
                if (is_string($slug) && $slug !== '') {
                    $tenant = Tenant::query()->where('slug', $slug)->first();
                }
            }
        }

        if ($tenant && $tenant->status !== 'active') {
            return ApiResponse::fail(ApiCode::BUSINESS, '租户未开通或已停用', 422);
        }

        if ($tenant) {
            $context->set($tenant);
            $request->attributes->set('tenant', $tenant);
        }

        return $next($request);
    }

    private function findExplicit(string $value): ?Tenant
    {
        $query = Tenant::query()->where('slug', $value);

        if (ctype_digit($value)) {
            $query->orWhere('id', (int) $value);
        }

        return $query->first();
    }

    /**
     * @return Tenant|false|null false 表示域名像租户子域但未命中
     */
    private function findFromHost(Request $request): Tenant|false|null
    {
        $host = strtolower($request->getHost());

        $byDomain = Tenant::query()->where('domain', $host)->first();
        if ($byDomain) {
            return $byDomain;
        }

        $slug = $this->subdomainSlug($host);
        if ($slug === null) {
            return null;
        }

        return Tenant::query()->where('slug', $slug)->first() ?? false;
    }

    private function subdomainSlug(string $host): ?string
    {
        if (filter_var($host, FILTER_VALIDATE_IP) || $host === 'localhost') {
            return null;
        }

        // 平台分配的主机不是租户子域，回落到 TENANT_DEFAULT_SLUG。
        // trycloudflare.com 只是临时通道；正式环境用 Render 的 onrender.com。
        foreach (['trycloudflare.com', 'onrender.com'] as $suffix) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                return null;
            }
        }

        $parts = explode('.', $host);
        $isLocalhostSub = count($parts) === 2 && $parts[1] === 'localhost';

        if (! $isLocalhostSub && count($parts) < 3) {
            return null;
        }

        $slug = $parts[0];

        if (in_array($slug, ['www', 'api', 'admin'], true)) {
            return null;
        }

        return $slug;
    }
}
