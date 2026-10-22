<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Post;
use App\Models\Tenant;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_and_favorite_are_idempotent_and_write_counts_back(): void
    {
        [$tenant, $author, $member, $post] = $this->scene();
        $token = $member->createToken('access')->plainTextToken;

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->postJson('/api/v1/interactions/like', [
                'target_type' => 'post',
                'target_id' => $post->id,
            ])
            ->assertUnauthorized();

        $liked = $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->postJson('/api/v1/interactions/like', [
                'target_type' => 'post',
                'target_id' => $post->id,
            ]);

        $liked->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.like_count', 1);

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->postJson('/api/v1/interactions/like', [
                'target_type' => 'post',
                'target_id' => $post->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.like_count', 1);

        $this->app['auth']->forgetGuards();

        $this->assertSame(1, $post->fresh()->like_count);

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->deleteJson('/api/v1/interactions/like?target_type=post&target_id='.$post->id)
            ->assertOk()
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('data.like_count', 0);

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->postJson('/api/v1/interactions/favorite', [
                'target_type' => 'post',
                'target_id' => $post->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.favorited', true)
            ->assertJsonPath('data.favorite_count', 1);

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->getJson('/api/v1/interactions/favorites')
            ->assertOk()
            ->assertJsonPath('data.items.0.target_type', 'post')
            ->assertJsonPath('data.items.0.target_id', $post->id)
            ->assertJsonPath('data.items.0.target.title', '公开笔记');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($token)
            ->getJson('/api/v1/interactions/status?targets=post:'.$post->id)
            ->assertOk()
            ->assertJsonPath('data.items.0.liked', false)
            ->assertJsonPath('data.items.0.favorited', true);

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($author->createToken('access')->plainTextToken)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.items.0.type', 'post.favorited');
    }

    public function test_comments_follow_post_review_and_notify_after_approval(): void
    {
        [$tenant, $author, $member, $post] = $this->scene();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_ADMIN,
            'nickname' => '圈主',
        ]);
        $pending = Post::query()->create([
            'tenant_id' => $tenant->id,
            'board_id' => $post->board_id,
            'user_id' => $author->id,
            'title' => '还在审',
            'content' => '待审正文',
            'status' => 'pending',
        ]);

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($member->createToken('access')->plainTextToken)
            ->postJson("/api/v1/posts/{$pending->id}/comments", ['content' => '不该写上'])
            ->assertStatus(422)
            ->assertJsonPath('code', 42201);

        $created = $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($member->createToken('access')->plainTextToken)
            ->postJson("/api/v1/posts/{$post->id}/comments", ['content' => '写得清楚']);

        $created->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.content', '写得清楚');

        $commentId = $created->json('data.id');

        $this->dropAuth();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->getJson("/api/v1/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($member->createToken('access')->plainTextToken)
            ->getJson("/api/v1/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.items.0.status', 'pending');

        $this->app['auth']->forgetGuards();

        $this->assertSame(0, $post->fresh()->comment_count);

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($admin->createToken('access')->plainTextToken)
            ->postJson("/api/v1/admin/comments/{$commentId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->assertSame(1, $post->fresh()->comment_count);

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->getJson("/api/v1/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.items.0.content', '写得清楚');

        $this->app['auth']->forgetGuards();

        $notices = $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($author->createToken('access')->plainTextToken)
            ->getJson('/api/v1/notifications');

        $notices->assertOk()
            ->assertJsonPath('data.items.0.type', 'post.commented');

        $noticeId = $notices->json('data.items.0.id');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($author->createToken('access')->plainTextToken)
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.count', 1);

        $read = $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($author->createToken('access')->plainTextToken)
            ->postJson("/api/v1/notifications/{$noticeId}/read");

        $read->assertOk();
        $this->assertNotNull($read->json('data.read_at'));

        $this->app['auth']->forgetGuards();

        $second = $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($member->createToken('access')->plainTextToken)
            ->postJson("/api/v1/posts/{$post->id}/comments", ['content' => '另一条']);

        $second->assertOk()->assertJsonPath('data.status', 'pending');
        $secondId = $second->json('data.id');

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($admin->createToken('access')->plainTextToken)
            ->postJson("/api/v1/admin/comments/{$secondId}/reject", ['reason' => '跑题'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->getJson("/api/v1/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);

        $this->app['auth']->forgetGuards();

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($member->createToken('access')->plainTextToken)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.items.0.type', 'comment.rejected');
    }

    public function test_post_review_writes_a_notification_for_the_author(): void
    {
        [$tenant, $author, $member, $post] = $this->scene();
        $pending = Post::query()->create([
            'tenant_id' => $tenant->id,
            'board_id' => $post->board_id,
            'user_id' => $author->id,
            'title' => '等审核',
            'content' => '正文',
            'status' => 'pending',
        ]);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_ADMIN,
        ]);

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($admin->createToken('access')->plainTextToken)
            ->postJson("/api/v1/admin/posts/{$pending->id}/approve")
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($author->createToken('access')->plainTextToken)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.items.0.type', 'post.approved')
            ->assertJsonPath('data.items.0.payload.post_id', $pending->id);

        $this->app['auth']->forgetGuards();

        $this->withHeader('X-Tenant-Id', $tenant->slug)
            ->withToken($member->createToken('access')->plainTextToken)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);
    }

    /**
     * @return array{0: Tenant, 1: User, 2: User, 3: Post}
     */
    private function scene(): array
    {
        $tenant = Tenant::query()->create([
            'name' => '演示',
            'slug' => 'demo',
            'status' => 'active',
            'plan_code' => 'free',
        ]);
        $board = Board::query()->create([
            'tenant_id' => $tenant->id,
            'name' => '综合讨论',
            'slug' => 'talk',
            'status' => 'published',
        ]);
        $author = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::MEMBER,
            'nickname' => '作者',
        ]);
        $member = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::MEMBER,
            'nickname' => '读者',
        ]);
        $post = Post::query()->create([
            'tenant_id' => $tenant->id,
            'board_id' => $board->id,
            'user_id' => $author->id,
            'title' => '公开笔记',
            'content' => '正文',
            'status' => 'published',
            'published_at' => now(),
        ]);

        return [$tenant, $author, $member, $post];
    }

    private function dropAuth(): void
    {
        unset($this->defaultHeaders['Authorization']);
        $this->app['auth']->forgetGuards();
    }
}
