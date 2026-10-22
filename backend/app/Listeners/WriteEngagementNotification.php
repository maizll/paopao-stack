<?php

namespace App\Listeners;

use App\Events\CommentVisibilityChanged;
use App\Events\EngagementToggled;
use App\Events\PostReviewed;
use App\Models\Comment;
use App\Models\Post;
use App\Models\SiteNotification;
use App\Models\User;

class WriteEngagementNotification
{
    public function handle(EngagementToggled|CommentVisibilityChanged|PostReviewed $event): void
    {
        if ($event instanceof PostReviewed) {
            $this->postReviewed($event);

            return;
        }

        if ($event instanceof CommentVisibilityChanged) {
            $this->commentChanged($event);

            return;
        }

        if (! in_array($event->action, ['like', 'favorite'], true) || $event->targetType !== 'post') {
            return;
        }

        $post = Post::query()->find($event->targetId);
        if (! $post || $post->user_id === $event->actorId) {
            return;
        }

        $actor = User::query()->find($event->actorId);
        $name = $actor?->nickname ?: '有人';
        $liked = $event->action === 'like';

        $this->write(
            $post->user_id,
            $liked ? 'post.liked' : 'post.favorited',
            $liked ? '收到一个赞' : '笔记被收藏',
            $liked
                ? $name.' 赞了你的笔记「'.$post->title.'」'
                : $name.' 收藏了你的笔记「'.$post->title.'」',
            ['post_id' => $post->id],
        );
    }

    private function postReviewed(PostReviewed $event): void
    {
        $post = Post::query()->find($event->postId);
        if (! $post) {
            return;
        }

        $approved = $event->decision === 'approved';
        $reason = trim((string) $event->reason);
        $body = $approved
            ? '你的笔记「'.$post->title.'」已公开。'
            : ($reason !== ''
                ? '你的笔记「'.$post->title.'」未通过：'.$reason
                : '你的笔记「'.$post->title.'」未通过审核。');

        $this->write(
            $post->user_id,
            $approved ? 'post.approved' : 'post.rejected',
            $approved ? '笔记已通过' : '笔记未通过',
            $body,
            ['post_id' => $post->id],
        );
    }

    private function commentChanged(CommentVisibilityChanged $event): void
    {
        if ($event->notice === null) {
            return;
        }

        $comment = Comment::withTrashed()->find($event->commentId);
        $post = Post::query()->find($event->postId);
        if (! $comment || ! $post) {
            return;
        }

        if ($event->notice === 'post.commented') {
            if ($comment->user_id === $post->user_id) {
                return;
            }
            $name = $comment->author?->nickname ?: User::query()->find($comment->user_id)?->nickname ?: '有人';
            $excerpt = mb_substr($comment->content, 0, 40);
            $this->write(
                $post->user_id,
                'post.commented',
                '笔记有新评论',
                $name.' 评论了「'.$post->title.'」：'.$excerpt,
                ['post_id' => $post->id, 'comment_id' => $comment->id],
            );

            return;
        }

        $reason = trim((string) $event->reason);
        if ($event->notice === 'comment.rejected') {
            $body = $reason !== ''
                ? '你在「'.$post->title.'」下的评论未通过：'.$reason
                : '你在「'.$post->title.'」下的评论未通过审核。';
            $this->write($comment->user_id, 'comment.rejected', '评论未通过', $body, [
                'post_id' => $post->id,
                'comment_id' => $comment->id,
            ]);

            return;
        }

        if ($event->notice === 'comment.hidden') {
            $this->write($comment->user_id, 'comment.hidden', '评论已屏蔽', '你在「'.$post->title.'」下的评论已被屏蔽。', [
                'post_id' => $post->id,
                'comment_id' => $comment->id,
            ]);
        }
    }

    /**
     * @param  array<string, int>  $payload
     */
    private function write(int $userId, string $type, string $title, string $body, array $payload): void
    {
        SiteNotification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => mb_substr($body, 0, 500),
            'payload' => $payload,
        ]);
    }
}
