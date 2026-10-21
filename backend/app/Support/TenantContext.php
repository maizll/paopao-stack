<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * 当前请求的租户。由 ResolveTenant 每请求重置，避免单例串租户。
 */
class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function forget(): void
    {
        $this->tenant = null;
    }
}
