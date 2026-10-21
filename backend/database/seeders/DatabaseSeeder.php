<?php

namespace Database\Seeders;

use App\Models\AppCategory;
use App\Models\AppVersion;
use App\Models\Board;
use App\Models\OpsBanner;
use App\Models\OpsGridItem;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Software;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use App\Support\UserRole;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->updateOrCreate(
            ['code' => 'free'],
            [
                'name' => '免费版',
                'is_default' => true,
                'quotas' => [
                    'boards' => 20,
                    'apps' => 50,
                    'admins' => 3,
                ],
            ],
        );

        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'demo'],
            [
                'name' => '泡泡栈演示租户',
                'domain' => 'demo.localhost',
                'status' => 'active',
                'plan_code' => 'free',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'super@paopao.local'],
            [
                'tenant_id' => null,
                'name' => '平台超管',
                'nickname' => '平台超管',
                'password' => 'password',
                'role' => UserRole::PLATFORM_SUPER_ADMIN,
                'status' => 'active',
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@demo.paopao.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => '租户管理员',
                'nickname' => '租户管理员',
                'password' => 'password',
                'role' => UserRole::TENANT_ADMIN,
                'status' => 'active',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'member@demo.paopao.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => '泡泡成员',
                'nickname' => '泡泡成员',
                'password' => 'password',
                'role' => UserRole::MEMBER,
                'status' => 'active',
            ],
        );

        app(TenantContext::class)->set($tenant);

        $general = Board::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'general'],
            [
                'name' => '综合讨论',
                'description' => '租户里的闲聊、公告和上手问题。',
                'sort' => 10,
                'status' => 'published',
                'post_count' => 1,
            ],
        );

        Board::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'apps'],
            [
                'name' => '软件交流',
                'description' => '聊已上架的软件、版本和用法。',
                'sort' => 20,
                'status' => 'published',
                'post_count' => 0,
            ],
        );

        $category = AppCategory::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'productivity'],
            [
                'name' => '效率工具',
                'sort' => 10,
            ],
        );

        $app = Software::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'paopao-notes'],
            [
                'category_id' => $category->id,
                'name' => '泡泡记事',
                'icon' => null,
                'summary' => '给演示租户准备的记事应用，用来串起商店和讨论区。',
                'description' => '这是脚手架里的示例软件。下载签发、付费和评分留到后续迭代。',
                'download_count' => 12,
                'status' => 'published',
            ],
        );

        AppVersion::query()->updateOrCreate(
            ['app_id' => $app->id, 'version_code' => 100],
            [
                'tenant_id' => $tenant->id,
                'version_name' => '1.0.0',
                'channel' => 'stable',
                'size' => 2048000,
                'checksum' => 'sha256:demo',
                'min_os' => 'Android 8',
                'changelog' => '首个可展示的示例版本。安装包尚未接入对象存储。',
                'storage_key' => null,
                'status' => 'published',
                'released_at' => now(),
            ],
        );

        Post::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'title' => '泡泡栈脚手架已就位'],
            [
                'board_id' => $general->id,
                'user_id' => $admin->id,
                'app_id' => $app->id,
                'content' => '这里是演示租户的第一篇已发布帖子。注册、逛版块、发待审帖、以及后台通过审核，都可以在这套骨架上走通。商城、支付、私信和短视频不在本轮。',
                'status' => 'published',
                'published_at' => now(),
            ],
        );

        OpsBanner::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'title' => '欢迎来到泡泡栈'],
            [
                'image' => '/banners/welcome.svg',
                'link_type' => 'board',
                'link_value' => (string) $general->id,
                'sort' => 10,
                'status' => 'published',
            ],
        );

        OpsGridItem::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'title' => '圈子'],
            [
                'icon' => 'board',
                'link_type' => 'board',
                'link_value' => (string) $general->id,
                'badge' => null,
                'sort' => 10,
                'status' => 'published',
            ],
        );

        OpsGridItem::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'title' => '软件商店'],
            [
                'icon' => 'app',
                'link_type' => 'app',
                'link_value' => (string) $app->id,
                'badge' => '新',
                'sort' => 20,
                'status' => 'published',
            ],
        );
    }
}
