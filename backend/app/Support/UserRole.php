<?php

namespace App\Support;

/**
 * 平台超管与租户管理员分离。普通成员走前台。
 */
final class UserRole
{
    public const MEMBER = 'member';

    public const TENANT_ADMIN = 'tenant_admin';

    public const PLATFORM_SUPER_ADMIN = 'platform_super_admin';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::MEMBER,
            self::TENANT_ADMIN,
            self::PLATFORM_SUPER_ADMIN,
        ];
    }

    public static function isAdmin(string $role): bool
    {
        return in_array($role, [self::TENANT_ADMIN, self::PLATFORM_SUPER_ADMIN], true);
    }
}
