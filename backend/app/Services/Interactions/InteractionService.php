<?php

namespace App\Services\Interactions;

use App\Events\EngagementToggled;
use App\Exceptions\ApiException;
use App\Models\Comment;
use App\Models\Interaction;
use App\Models\Post;
use App\Models\Software;
use App\Models\User;
use App\Support\ApiCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class InteractionService
{
    /**
     * @return array<string, mixed>
     */
    public function like(User $user, string $type, int $id): array
    {
        return $this->toggle($user, $type, $id, 'like', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function unlike(User $user, string $type, int $id): array
    {
        return $this->toggle($user, $type, $id, 'like', false);
    }

    /**
     * @return array<string, mixed>
     */
    public function favorite(User $user, string $type, int $id): array
    {
        return $this->toggle($user, $type, $id, 'favorite', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function unfavorite(User $user, string $type, int $id): array
    {
        return $this->toggle($user, $type, $id, 'favorite', false);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function favorites(User $user, int $perPage, int $page): LengthAwarePaginator
    {
        $paginator = Interaction::query()
            ->where('user_id', $user->id)
            ->where('kind', 'favorite')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->getCollection()->transform(fn (Interaction $row) => $this->favoriteItem($row));

        return $paginator;
    }

    /**
     * @param  list<array{0: string, 1: int}>  $targets
     * @return list<array<string, mixed>>
     */
    public function status(User $user, array $targets): array
    {
        if ($targets === []) {
            return [];
        }

        $rows = Interaction::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($targets): void {
                foreach ($targets as [$type, $id]) {
                    $query->orWhere(function ($inner) use ($type, $id): void {
                        $inner->where('target_type', $type)->where('target_id', $id);
                    });
                }
            })
            ->get();

        $liked = [];
        $favorited = [];
        foreach ($rows as $row) {
            $key = $row->target_type.':'.$row->target_id;
            if ($row->kind === 'like') {
                $liked[$key] = true;
            }
            if ($row->kind === 'favorite') {
                $favorited[$key] = true;
            }
        }

        $items = [];
        foreach ($targets as [$type, $id]) {
            $key = $type.':'.$id;
            $items[] = [
                'target_type' => $type,
                'target_id' => $id,
                'liked' => isset($liked[$key]),
                'favorited' => isset($favorited[$key]),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function toggle(User $user, string $type, int $id, string $kind, bool $on): array
    {
        $this->assertTarget($type, $id);

        $query = Interaction::query()
            ->where('user_id', $user->id)
            ->where('target_type', $type)
            ->where('target_id', $id)
            ->where('kind', $kind);

        $changed = false;
        if ($on) {
            $row = $query->first();
            if (! $row) {
                Interaction::query()->create([
                    'user_id' => $user->id,
                    'target_type' => $type,
                    'target_id' => $id,
                    'kind' => $kind,
                ]);
                $changed = true;
            }
        } elseif ($query->delete() > 0) {
            $changed = true;
        }

        if ($changed) {
            event(new EngagementToggled($on ? $kind : 'un'.$kind, $type, $id, $user->id));
            Log::info('interaction.toggle', [
                'user_id' => $user->id,
                'target_type' => $type,
                'target_id' => $id,
                'kind' => $kind,
                'on' => $on,
            ]);
        }

        return $this->state($user, $type, $id);
    }

    private function assertTarget(string $type, int $id): void
    {
        if (! in_array($type, ['post', 'comment', 'app'], true)) {
            throw new ApiException(ApiCode::VALIDATION, '目标类型不正确', 400);
        }

        if ($type === 'post') {
            $post = Post::query()->find($id);
            if (! $post) {
                throw new ApiException(ApiCode::NOT_FOUND, '笔记不存在', 404);
            }
            if ($post->status !== 'published') {
                throw new ApiException(ApiCode::BUSINESS, '笔记还未公开', 422);
            }

            return;
        }

        if ($type === 'comment') {
            $comment = Comment::query()->find($id);
            if (! $comment) {
                throw new ApiException(ApiCode::NOT_FOUND, '评论不存在', 404);
            }
            if ($comment->status !== 'published') {
                throw new ApiException(ApiCode::BUSINESS, '评论还未公开', 422);
            }

            return;
        }

        $app = Software::query()->find($id);
        if (! $app) {
            throw new ApiException(ApiCode::NOT_FOUND, '软件不存在', 404);
        }
        if ($app->status !== 'published') {
            throw new ApiException(ApiCode::BUSINESS, '软件还未公开', 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function state(User $user, string $type, int $id): array
    {
        $kinds = Interaction::query()
            ->where('user_id', $user->id)
            ->where('target_type', $type)
            ->where('target_id', $id)
            ->pluck('kind')
            ->all();

        $likeCount = null;
        $favoriteCount = null;
        if ($type === 'post') {
            $post = Post::query()->find($id);
            $likeCount = $post?->like_count;
            $favoriteCount = $post?->favorite_count;
        } elseif ($type === 'comment') {
            $likeCount = Comment::query()->find($id)?->like_count;
        }

        return [
            'target_type' => $type,
            'target_id' => $id,
            'liked' => in_array('like', $kinds, true),
            'favorited' => in_array('favorite', $kinds, true),
            'like_count' => $likeCount,
            'favorite_count' => $favoriteCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function favoriteItem(Interaction $row): array
    {
        $target = null;
        if ($row->target_type === 'post') {
            $post = Post::query()->find($row->target_id);
            if ($post) {
                $target = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'cover' => $post->cover,
                    'status' => $post->status,
                ];
            }
        } elseif ($row->target_type === 'comment') {
            $comment = Comment::query()->find($row->target_id);
            if ($comment) {
                $target = [
                    'id' => $comment->id,
                    'title' => mb_substr($comment->content, 0, 40),
                    'cover' => null,
                    'status' => $comment->status,
                    'post_id' => $comment->post_id,
                ];
            }
        } elseif ($row->target_type === 'app') {
            $app = Software::query()->find($row->target_id);
            if ($app) {
                $target = [
                    'id' => $app->id,
                    'title' => $app->name,
                    'cover' => $app->icon,
                    'status' => $app->status,
                ];
            }
        }

        return [
            'id' => $row->id,
            'target_type' => $row->target_type,
            'target_id' => $row->target_id,
            'target' => $target,
            'created_at' => $row->created_at?->toIso8601String(),
        ];
    }
}
