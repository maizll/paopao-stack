<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * 业务表全局租户范围。没有当前租户时不返回任何行，避免漏过滤串租户。
 * User 不使用本 trait：登录与平台超管要跨租户查账号，由调用方显式 where tenant_id。
 *
 * @mixin Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId) {
                $builder->where($builder->getModel()->qualifyColumn('tenant_id'), $tenantId);

                return;
            }

            $builder->whereRaw('0 = 1');
        });

        static::creating(function (Model $model): void {
            if (! $model->getAttribute('tenant_id') && ($id = app(TenantContext::class)->id())) {
                $model->setAttribute('tenant_id', $id);
            }
        });
    }
}
