<?php

namespace App\Support;

/**
 * 全局错误码。只允许在 docs/api-planning.md §1.4 追加，模块不得改表结构。
 */
final class ApiCode
{
    public const OK = 0;

    public const VALIDATION = 40001;

    public const UNAUTHENTICATED = 40101;

    public const TOKEN_EXPIRED = 40102;

    public const FORBIDDEN = 40301;

    public const NOT_FOUND = 40401;

    public const CONFLICT = 40901;

    public const BUSINESS = 42201;

    public const THROTTLED = 42901;

    public const SERVER = 50001;
}
