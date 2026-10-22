<?php

namespace Tests\Feature;

use App\Models\AppCategory;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreOpsUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_software_stays_hidden_until_an_admin_approves_it(): void
    {
        $tenant = $this->tenant();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_ADMIN,
        ]);
        $member = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::MEMBER,
        ]);
        $category = AppCategory::query()->create([
            'tenant_id' => $tenant->id,
            'name' => '效率工具',
            'slug' => 'productivity',
            'sort' => 10,
        ]);

        $token = $admin->createToken('access')->plainTextToken;
        $created = $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->postJson('/api/v1/admin/apps', [
                'name' => '夜灯',
                'category_id' => $category->id,
                'summary' => '睡前把屏幕调暗。',
                'description' => '待审软件，通过后才出现在发现页。',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.name', '夜灯');

        $id = $created->json('data.id');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->getJson('/api/v1/apps')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($member->createToken('access')->plainTextToken)
            ->postJson("/api/v1/admin/apps/{$id}/approve")
            ->assertForbidden();

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->putJson("/api/v1/admin/apps/{$id}", [
                'name' => '夜灯',
                'category_id' => $category->id,
                'summary' => '睡前把屏幕调暗一点。',
                'status' => 'published',
            ])
            ->assertOk()
            ->assertJsonPath('data.summary', '睡前把屏幕调暗一点。')
            ->assertJsonPath('data.status', 'draft');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->postJson("/api/v1/admin/apps/{$id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->getJson('/api/v1/apps')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.name', '夜灯');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->postJson("/api/v1/admin/apps/{$id}/reject", ['reason' => '介绍不完整'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->getJson('/api/v1/apps')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);
    }

    public function test_draft_banner_is_hidden_and_published_grid_is_public(): void
    {
        $tenant = $this->tenant();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_ADMIN,
        ]);
        $token = $admin->createToken('access')->plainTextToken;

        $banner = $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->postJson('/api/v1/admin/ops/banners', [
                'title' => '夜间活动',
                'image' => 'https://cdn.example.com/night.jpg',
                'link_type' => 'url',
                'link_value' => 'https://example.com/night',
                'sort' => 1,
                'status' => 'draft',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->getJson('/api/v1/ops/banners')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->putJson('/api/v1/admin/ops/banners/'.$banner->json('data.id'), [
                'title' => '夜间活动',
                'image' => 'https://cdn.example.com/night.jpg',
                'link_type' => 'board',
                'link_value' => '3',
                'sort' => 1,
                'status' => 'published',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->getJson('/api/v1/ops/banners')
            ->assertOk()
            ->assertJsonPath('data.0.title', '夜间活动')
            ->assertJsonPath('data.0.link_type', 'board');

        $grid = $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->postJson('/api/v1/admin/ops/grid', [
                'title' => '发现',
                'icon' => 'app',
                'link_type' => 'app',
                'link_value' => '1',
                'badge' => '新',
                'sort' => 2,
                'status' => 'published',
            ])
            ->assertOk();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->getJson('/api/v1/ops/grid')
            ->assertOk()
            ->assertJsonPath('data.0.title', '发现');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->deleteJson('/api/v1/admin/ops/grid/'.$grid->json('data.id'))
            ->assertOk();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->getJson('/api/v1/ops/grid')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_member_can_upload_an_image_and_guest_cannot(): void
    {
        Storage::fake('public');
        $tenant = $this->tenant();
        $member = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::MEMBER,
        ]);

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->post('/api/v1/uploads', [
                'file' => $this->png(),
            ], ['Accept' => 'application/json'])
            ->assertUnauthorized();

        $response = $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($member->createToken('access')->plainTextToken)
            ->post('/api/v1/uploads', [
                'file' => $this->png(),
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $path = $response->json('data.path');
        $this->assertIsString($path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('/storage/'.$path, $response->json('data.url'));
    }

    private function png(): UploadedFile
    {
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent('cover.png', $bytes);
    }

    private function tenant(): \App\Models\Tenant
    {
        return \App\Models\Tenant::query()->create([
            'name' => '演示',
            'slug' => 'demo',
            'status' => 'active',
            'plan_code' => 'free',
        ]);
    }
}
