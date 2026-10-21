<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use App\Support\UserRole;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::PLATFORM_SUPER_ADMIN;
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::PLATFORM_SUPER_ADMIN;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::PLATFORM_SUPER_ADMIN;
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::PLATFORM_SUPER_ADMIN;
    }
}
