<?php

namespace App\Listeners;

use App\Events\CommentVisibilityChanged;
use App\Events\EngagementToggled;
use App\Models\Comment;
use App\Models\Post;

class SyncEngagementCounters
{
    public function handle(EngagementToggled|CommentVisibilityChanged $event): void
    {
        if ($event instanceof CommentVisibilityChanged) {
            $this->shiftPost($event->postId, 'comment_count', $event->countDelta);

            return;
        }

        $field = match ($event->action) {
            'like', 'unlike' => 'like_count',
            'favorite', 'unfavorite' => 'favorite_count',
            default => null,
        };

        if ($field === null) {
            return;
        }

        $delta = in_array($event->action, ['like', 'favorite'], true) ? 1 : -1;

        if ($event->targetType === 'post' && in_array($field, ['like_count', 'favorite_count'], true)) {
            $this->shiftPost($event->targetId, $field, $delta);
        }

        if ($event->targetType === 'comment' && $field === 'like_count') {
            $comment = Comment::query()->find($event->targetId);
            if (! $comment) {
                return;
            }
            $comment->like_count = max(0, $comment->like_count + $delta);
            $comment->save();
        }
    }

    private function shiftPost(int $postId, string $field, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $post = Post::query()->find($postId);
        if (! $post) {
            return;
        }

        $post->{$field} = max(0, (int) $post->{$field} + $delta);
        $post->save();
    }
}
