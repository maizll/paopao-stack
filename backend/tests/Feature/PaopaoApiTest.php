<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Plan;
use App\Models\Post;
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

    public function test_tenant_admin_can_edit_a_post_without_changing_status(): void
    {
        $alpha = $this->tenant('alpha');
        $beta = $this->tenant('beta');
        $admin = User::factory()->create([
            'tenant_id' => $alpha->id,
            'role' => UserRole::TENANT_ADMIN,
        ]);
        $author = User::factory()->create([
            'tenant_id' => $alpha->id,
            'role' => UserRole::MEMBER,
        ]);
        $betaAdmin = User::factory()->create([
            'tenant_id' => $beta->id,
            'role' => UserRole::TENANT_ADMIN,
        ]);
        $talk = Board::query()->create([
            'tenant_id' => $alpha->id,
            'name' => '综合讨论',
            'slug' => 'talk',
            'status' => 'published',
        ]);
        $apps = Board::query()->create([
            'tenant_id' => $alpha->id,
            'name' => '软件交流',
            'slug' => 'apps',
            'status' => 'published',
        ]);
        $foreign = Board::query()->create([
            'tenant_id' => $beta->id,
            'name' => '乙版',
            'slug' => 'beta-board',
            'status' => 'published',
        ]);
        $post = Post::query()->create([
            'tenant_id' => $alpha->id,
            'board_id' => $talk->id,
            'user_id' => $author->id,
            'title' => '原标题',
            'content' => '原正文',
            'status' => 'pending',
        ]);

        $token = $admin->createToken('access')->plainTextToken;
        $payload = [
            'title' => '改过的标题',
            'content' => '改过的正文',
            'board_id' => $apps->id,
            'cover' => 'https://example.com/cover.jpg',
            'status' => 'published',
        ];

        $this->withHeader('X-Tenant-Id', $alpha->slug)
            ->withToken($token)
            ->patchJson("/api/v1/admin/posts/{$post->id}", $payload)
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.title', '改过的标题')
            ->assertJsonPath('data.content', '改过的正文')
            ->assertJsonPath('data.cover', 'https://example.com/cover.jpg')
            ->assertJsonPath('data.board.id', $apps->id)
            ->assertJsonPath('data.status', 'pending');

        $this->withHeader('X-Tenant-Id', $alpha->slug)
            ->withToken($token)
            ->putJson("/api/v1/admin/posts/{$post->id}", [
                'title' => '再改一次',
                'content' => '正文还在',
                'board_id' => $talk->id,
                'cover' => '',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', '再改一次')
            ->assertJsonPath('data.cover', null)
            ->assertJsonPath('data.status', 'pending');

        $this->withHeader('X-Tenant-Id', $alpha->slug)
            ->withToken($token)
            ->patchJson("/api/v1/admin/posts/{$post->id}", [
                'title' => '不该换到别的租户',
                'content' => '正文',
                'board_id' => $foreign->id,
            ])
            ->assertNotFound()
            ->assertJsonPath('code', 40401);

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $beta->slug)
            ->withToken($betaAdmin->createToken('access')->plainTextToken)
            ->patchJson("/api/v1/admin/posts/{$post->id}", [
                'title' => '串租户',
                'content' => '正文',
                'board_id' => $foreign->id,
            ])
            ->assertNotFound()
            ->assertJsonPath('code', 40401);

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $alpha->slug)
            ->withToken($author->createToken('access')->plainTextToken)
            ->patchJson("/api/v1/admin/posts/{$post->id}", [
                'title' => '成员不能审',
                'content' => '正文',
                'board_id' => $talk->id,
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 40301);

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $alpha->slug)
            ->withToken($token)
            ->postJson("/api/v1/admin/posts/{$post->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.title', '再改一次')
            ->assertJsonPath('data.status', 'published');
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
