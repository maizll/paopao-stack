<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaopaoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_uses_the_response_envelope(): void
    {
        $response = $this->getJson('/api/v1/system/health');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('message', 'ok')
            ->assertJsonPath('data.app', '泡泡栈')
            ->assertJsonPath('data.status', 'ok');
    }

    public function test_unknown_tenant_header_is_not_found(): void
    {
        $this->withHeader('X-Tenant-Id', 'missing')
            ->getJson('/api/v1/system/health')
            ->assertNotFound()
            ->assertJsonPath('code', 40401);
    }

    public function test_boards_require_a_tenant_when_no_default_is_configured(): void
    {
        $this->getJson('/api/v1/boards')
            ->assertStatus(400)
            ->assertJsonPath('code', 40001);
    }

    public function test_register_login_and_me(): void
    {
        $tenant = $this->tenant('alpha');

        $registered = $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->postJson('/api/v1/auth/register', [
                'account' => 'wave@example.com',
                'password' => 'password123',
                'nickname' => '小波',
            ]);

        $registered->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.nickname', '小波')
            ->assertJsonPath('data.user.roles.0', UserRole::MEMBER);

        $token = $registered->json('data.access_token');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.nickname', '小波')
            ->assertJsonPath('data.permissions', []);
    }

    public function test_posts_are_scoped_to_the_current_tenant(): void
    {
        $alpha = $this->tenant('alpha');
        $beta = $this->tenant('beta');

        Board::query()->create([
            'tenant_id' => $alpha->id,
            'name' => '甲版',
            'slug' => 'alpha-board',
            'status' => 'published',
        ]);

        $this->withHeader('X-Tenant-Id', $alpha->slug)
            ->getJson('/api/v1/boards')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'alpha-board');

        $this->withHeader('X-Tenant-Id', $beta->slug)
            ->getJson('/api/v1/boards')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_member_cannot_list_platform_tenants(): void
    {
        $tenant = $this->tenant('alpha');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::MEMBER,
        ]);

        $token = $user->createToken('access')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/admin/tenants')
            ->assertForbidden()
            ->assertJsonPath('code', 40301);
    }

    public function test_platform_admin_can_open_a_tenant(): void
    {
        Plan::query()->create([
            'code' => 'free',
            'name' => '免费版',
            'is_default' => true,
        ]);

        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => UserRole::PLATFORM_SUPER_ADMIN,
        ]);

        $token = $admin->createToken('access')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/admin/tenants', [
                'name' => '新租户',
                'slug' => 'fresh',
                'plan_code' => 'free',
            ])
            ->assertOk()
            ->assertJsonPath('data.slug', 'fresh')
            ->assertJsonPath('data.status', 'active');
    }

    public function test_validation_errors_use_the_shared_code(): void
    {
        $tenant = $this->tenant('alpha');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->postJson('/api/v1/auth/register', [
                'account' => 'not-an-account',
                'password' => 'short',
            ])
            ->assertStatus(400)
            ->assertJsonPath('code', 40001)
            ->assertJsonStructure(['data' => ['errors' => ['account', 'password']]]);
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'plan_code' => 'free',
        ]);
    }
}
