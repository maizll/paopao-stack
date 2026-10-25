<?php

namespace Tests\Feature;

use App\Exceptions\ApiException;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AppShell;
use App\Support\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase8ShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_rejects_unknown_pages_skins_and_empty_sets(): void
    {
        try {
            AppShell::normalize(['modules' => []]);
            $this->fail('空页面应该拒绝。');
        } catch (ApiException $e) {
            $this->assertSame('至少留一个页面。', $e->getMessage());
        }

        try {
            AppShell::normalize(['modules' => ['wallet']]);
            $this->fail('未知页面应该拒绝。');
        } catch (ApiException $e) {
            $this->assertSame('没有这个页面。', $e->getMessage());
        }

        try {
            AppShell::normalize(['modules' => ['home'], 'skins' => ['home' => 'neon']]);
            $this->fail('未知皮肤应该拒绝。');
        } catch (ApiException $e) {
            $this->assertSame('首页样式不存在。', $e->getMessage());
        }

        try {
            AppShell::normalize(['modules' => ['home', 'mine'], 'tabbar' => 'dock']);
            $this->fail('未知底栏应该拒绝。');
        } catch (ApiException $e) {
            $this->assertSame('底栏样式不存在。', $e->getMessage());
        }
    }

    public function test_trim_drops_pages_that_are_not_enabled_and_keeps_order(): void
    {
        $shell = AppShell::normalize([
            'modules' => ['mine', 'home', 'mall'],
            'skins' => ['home' => 'magazine', 'mall' => 'poster', 'mine' => 'grid'],
            'tabbar' => 'raised',
        ]);

        $this->assertSame(['home', 'mall', 'mine'], $shell['modules']);
        $pack = AppShell::trim($shell);
        $this->assertSame(['home', 'mall', 'mine'], array_column($pack['tabs'], 'id'));
        $this->assertSame(['community', 'discover', 'messages'], array_column($pack['omitted'], 'id'));
        $this->assertSame('pages/mall/home', $pack['tabs'][1]['path']);
    }

    public function test_admin_can_save_a_shell_and_the_app_reads_the_trimmed_tabs(): void
    {
        $tenant = Tenant::query()->create([
            'name' => '演示',
            'slug' => 'demo',
            'status' => 'active',
            'plan_code' => 'free',
        ]);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_ADMIN,
            'nickname' => '管理员',
            'status' => 'active',
        ]);

        $this->withHeader('X-Tenant-Id', 'demo')
            ->getJson('/api/v1/shell')
            ->assertOk()
            ->assertJsonPath('data.tabbar', 'labeled')
            ->assertJsonCount(6, 'data.pack.tabs')
            ->assertJsonCount(0, 'data.pack.omitted');

        $saved = $this->withHeader('X-Tenant-Id', 'demo')
            ->withToken($admin->createToken('access')->plainTextToken)
            ->putJson('/api/v1/admin/shell', [
                'modules' => ['discover', 'mine'],
                'skins' => ['discover' => 'grid', 'mine' => 'card'],
                'tabbar' => 'icon',
            ])
            ->assertOk()
            ->json('data');

        $this->assertSame(['discover', 'mine'], $saved['modules']);
        $this->assertSame('grid', $saved['skins']['discover']);
        $this->assertSame(['discover', 'mine'], array_column($saved['pack']['tabs'], 'id'));
        $this->assertSame(['home', 'community', 'mall', 'messages'], array_column($saved['pack']['omitted'], 'id'));

        $this->app['auth']->forgetGuards();
        $public = $this->withHeader('X-Tenant-Id', 'demo')
            ->getJson('/api/v1/shell')
            ->assertOk()
            ->json('data');
        $this->assertSame('icon', $public['tabbar']);
        $this->assertSame(['discover', 'mine'], array_column($public['pack']['tabs'], 'id'));

        $this->assertTrue(AuditLog::query()->where('action', 'shell.update')->exists());
    }

    public function test_normalize_accepts_custom_pages_and_subpages(): void
    {
        $shell = AppShell::normalize([
            'modules' => ['home', 'mine'],
            'skins' => ['home' => 'feed', 'mine' => 'plain'],
            'tabbar' => 'labeled',
            'customPages' => [[
                'id' => 'custom_lottery',
                'label' => '抽奖',
                'path' => 'pages/lottery/home',
                'summary' => '抽奖活动',
            ]],
            'subpages' => [
                'home' => [
                    ['id' => 'home_main', 'label' => '首页', 'path' => 'pages/home/home'],
                    ['id' => 'home_feed', 'label' => '推荐', 'path' => 'pages/home/feed'],
                ],
            ],
        ]);

        $this->assertSame(['home', 'mine'], $shell['modules']);
        $this->assertCount(1, $shell['customPages']);
        $this->assertSame('custom_lottery', $shell['customPages'][0]['id']);
        $this->assertCount(2, $shell['subpages']['home']);

        $pack = AppShell::trim($shell);
        $ids = array_column($pack['tabs'], 'id');
        $this->assertSame(['home', 'mine', 'custom_lottery'], $ids);
        $this->assertTrue($pack['tabs'][2]['custom'] ?? false);
    }

    public function test_normalize_rejects_too_many_pages(): void
    {
        $customs = [];
        for ($i = 0; $i < 20; $i++) {
            $customs[] = [
                'id' => 'custom_p'.$i,
                'label' => '页'.$i,
                'path' => 'pages/extra/p'.$i,
                'summary' => '',
            ];
        }
        try {
            AppShell::normalize([
                'modules' => ['home'],
                'skins' => ['home' => 'feed'],
                'tabbar' => 'labeled',
                'customPages' => $customs,
            ]);
            $this->fail('超限应被拒绝。');
        } catch (ApiException $e) {
            $this->assertSame('最多选择 20 个页面。', $e->getMessage());
        }
    }
}
