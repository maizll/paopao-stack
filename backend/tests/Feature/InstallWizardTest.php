<?php

namespace Tests\Feature;

use App\Support\Install\Installer;
use App\Support\Install\InstallLock;
use App\Support\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstallWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_lock_sends_visitors_to_the_wizard_and_blocks_the_api(): void
    {
        $this->unlock();

        $this->get('/')->assertRedirect('/install');
        $this->get('/install')->assertOk()->assertSee('环境检测');

        $this->getJson('/api/v1/system/health')
            ->assertStatus(503)
            ->assertJsonPath('code', 50301)
            ->assertJsonPath('message', '系统尚未安装');
    }

    public function test_environment_check_reports_php_and_keeps_redis_optional(): void
    {
        $this->unlock();

        $response = $this->getJson('/install/environment');

        $response->assertOk()->assertJsonPath('code', 0);
        $items = $response->json('data.items');
        $php = collect($items)->firstWhere('key', 'php');
        $redis = collect($items)->firstWhere('key', 'redis');

        $this->assertTrue($php['ok']);
        $this->assertSame('required', $php['level']);
        $this->assertSame('optional', $redis['level']);
        $this->assertTrue($response->json('data.ready'));
    }

    public function test_database_test_rejects_a_closed_port_with_a_clear_message(): void
    {
        $this->unlock();

        $this->postJson('/install/database/test', [
            'host' => '127.0.0.1',
            'port' => 1,
            'database' => 'paopao',
            'username' => 'paopao',
            'password' => 'wrong',
        ])->assertStatus(422)
            ->assertJsonPath('code', 42201)
            ->assertJsonPath('message', '连不上数据库主机，请检查地址、端口和数据库是否已启动。');
    }

    public function test_admin_password_must_be_confirmed(): void
    {
        $this->unlock();

        $this->postJson('/install/admin', [
            'nickname' => '平台超管',
            'email' => 'super@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ])->assertStatus(422)
            ->assertJsonPath('code', 40001)
            ->assertJsonPath('message', '两次输入的密码不一致。');
    }

    public function test_run_requires_the_previous_steps(): void
    {
        $this->unlock();

        $this->postJson('/install/run')
            ->assertStatus(422)
            ->assertJsonPath('message', '请先完成环境检测、数据库、管理员和站点信息。');
    }

    public function test_locked_install_refuses_to_run_again(): void
    {
        $this->postJson('/install/run')
            ->assertStatus(409)
            ->assertJsonPath('code', 40901);
    }

    public function test_installer_migrates_seeds_and_writes_a_lock_without_touching_env(): void
    {
        $lock = storage_path('framework/testing/installer-run.lock');
        $sqlite = storage_path('framework/testing/installer-test.sqlite');
        @unlink($lock);
        @unlink($sqlite);
        touch($sqlite);
        config(['paopao.install_lock' => $lock]);

        $env = base_path('.env');
        $before = is_file($env) ? hash('sha256', (string) file_get_contents($env)) : null;

        $result = app(Installer::class)->run(
            ['driver' => 'sqlite', 'database' => $sqlite],
            ['email' => 'installer@paopao.local', 'password' => 'password', 'nickname' => '安装管理员'],
            [
                'site_name' => '泡泡栈',
                'app_url' => 'http://127.0.0.1:43125',
                'tenant_name' => '安装租户',
                'tenant_slug' => 'demo',
                'seed_demo' => true,
            ],
            false,
        );

        $this->assertContains('已写入演示数据：示例成员、版块、帖子和软件。', $result['log']);
        $this->assertFileExists($lock);
        $this->assertSame('installer@paopao.local', InstallLock::read()['admin_email']);

        $admin = DB::connection('paopao_install')->table('users')->where('email', 'installer@paopao.local')->first();
        $this->assertNotNull($admin);
        $this->assertSame(UserRole::PLATFORM_SUPER_ADMIN, $admin->role);
        $this->assertNull($admin->tenant_id);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertSame(1, DB::connection('paopao_install')->table('posts')->where('title', '泡泡栈脚手架已就位')->count());

        if ($before !== null) {
            $this->assertSame($before, hash('sha256', (string) file_get_contents($env)));
        }

        @unlink($lock);
        @unlink($sqlite);
    }

    private function unlock(): void
    {
        $lock = storage_path('framework/testing/install.lock');
        if (is_file($lock)) {
            unlink($lock);
        }
    }
}
