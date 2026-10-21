<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Support\ApiCode;
use App\Support\TenantContext;
use App\Support\UserRole;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * 客户端契约按 Bearer access_token 返回。
     * 本骨架签发 Sanctum personal access token，而不是签名 JWT。
     * 替换签发器时保持响应字段不变。
     *
     * @return array<string, mixed>
     */
    public function register(string $account, string $password, ?string $nickname): array
    {
        $tenant = app(TenantContext::class)->get();

        if (! $tenant) {
            throw new ApiException(ApiCode::VALIDATION, '缺少租户：请设置请求头 X-Tenant-Id 或使用租户域名', 400);
        }

        if (! config('paopao.register_enabled')) {
            throw new ApiException(ApiCode::BUSINESS, '当前未开放注册', 422);
        }

        if ($this->findByAccount($account)) {
            throw new ApiException(ApiCode::CONFLICT, '账号已注册', 409);
        }

        [$email, $phone] = $this->splitAccount($account);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'nickname' => $nickname ?: $this->defaultNickname($account),
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'role' => UserRole::MEMBER,
            'status' => 'active',
        ]);

        return $this->issue($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function login(string $account, string $password, bool $adminsOnly = false): array
    {
        $user = $this->findByAccount($account);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new ApiException(ApiCode::UNAUTHENTICATED, '账号或密码错误', 401);
        }

        if ($user->status !== 'active') {
            throw new ApiException(ApiCode::FORBIDDEN, '账号已封禁', 403);
        }

        if ($adminsOnly && ! UserRole::isAdmin($user->role)) {
            throw new ApiException(ApiCode::FORBIDDEN, '无权限', 403);
        }

        $this->assertTenantAlignment($user);

        return $this->issue($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function refresh(User $user): array
    {
        $user->currentAccessToken()?->delete();

        return $this->issue($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function issue(User $user): array
    {
        $minutes = (int) config('sanctum.expiration', 1440);
        $expiresAt = now()->addMinutes($minutes);
        $token = $user->createToken('access', ['*'], $expiresAt);

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => $minutes * 60,
            'user' => $user->toAuthArray(),
        ];
    }

    private function assertTenantAlignment(User $user): void
    {
        if ($user->role === UserRole::PLATFORM_SUPER_ADMIN) {
            return;
        }

        $tenantId = app(TenantContext::class)->id();

        if (! $tenantId) {
            throw new ApiException(ApiCode::VALIDATION, '缺少租户：请设置请求头 X-Tenant-Id 或使用租户域名', 400);
        }

        if ($user->tenant_id !== $tenantId) {
            throw new ApiException(ApiCode::FORBIDDEN, '账号不属于当前租户', 403);
        }
    }

    private function findByAccount(string $account): ?User
    {
        $account = trim($account);

        return User::query()
            ->where('email', $account)
            ->orWhere('phone', $account)
            ->first();
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitAccount(string $account): array
    {
        if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            return [$account, null];
        }

        return [null, $account];
    }

    private function defaultNickname(string $account): string
    {
        $local = strstr($account, '@', true);

        return $local !== false && $local !== '' ? $local : '泡泡用户';
    }
}
