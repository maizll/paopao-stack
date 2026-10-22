<?php

namespace App\Services\Comments;

use App\Events\CommentVisibilityChanged;
use App\Exceptions\ApiException;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Support\ApiCode;
use Illuminate\Support\Facades\Log;

class CommentService
{
    public function store(User $user, Post $post, string $content, ?int $parentId, ?int $replyToUserId): Comment
    {
        if ($post->status !== 'published') {
            throw new ApiException(ApiCode::BUSINESS, '笔记还未公开，不能评论', 422);
        }

        $content = trim(strip_tags($content));
        if ($content === '') {
            throw new ApiException(ApiCode::VALIDATION, '评论不能为空', 400);
        }

        if ($parentId) {
            $parent = Comment::query()->where('post_id', $post->id)->find($parentId);
            if (! $parent) {
                throw new ApiException(ApiCode::NOT_FOUND, '要回复的评论不存在', 404);
            }
        }

        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'reply_to_user_id' => $replyToUserId,
            'content' => mb_substr($content, 0, 500),
            'status' => 'pending',
        ]);

        Log::info('comment.store', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'comment_id' => $comment->id,
        ]);

        $comment->load('author');

        return $comment;
    }

    public function publish(Comment $comment): Comment
    {
        if ($comment->status === 'published') {
            throw new ApiException(ApiCode::CONFLICT, '评论已发布', 409);
        }

        $comment->status = 'published';
        $comment->save();
        $this->changed($comment, 1, 'post.commented', null);

        return $comment->load(['author', 'post']);
    }

    public function reject(Comment $comment, ?string $reason): Comment
    {
        if ($comment->status === 'rejected') {
            throw new ApiException(ApiCode::CONFLICT, '评论已拒绝', 409);
        }

        $delta = $comment->status === 'published' ? -1 : 0;
        $comment->status = 'rejected';
        $comment->save();
        $this->changed($comment, $delta, 'comment.rejected', $reason);

        return $comment->load(['author', 'post']);
    }

    public function hide(Comment $comment): Comment
    {
        if ($comment->status === 'hidden') {
            throw new ApiException(ApiCode::CONFLICT, '评论已屏蔽', 409);
        }

        $delta = $comment->status === 'published' ? -1 : 0;
        $comment->status = 'hidden';
        $comment->save();
        $this->changed($comment, $delta, 'comment.hidden', null);

        return $comment->load(['author', 'post']);
    }

    public function remove(Comment $comment): void
    {
        $delta = $comment->status === 'published' ? -1 : 0;
        $commentId = $comment->id;
        $postId = $comment->post_id;
        $comment->delete();

        event(new CommentVisibilityChanged($commentId, $postId, $delta, null, null));
        Log::info('comment.remove', [
            'comment_id' => $commentId,
            'post_id' => $postId,
        ]);
    }

    private function changed(Comment $comment, int $delta, ?string $notice, ?string $reason): void
    {
        event(new CommentVisibilityChanged($comment->id, $comment->post_id, $delta, $notice, $reason));
        Log::info('comment.moderate', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
            'notice' => $notice,
            'delta' => $delta,
        ]);
    }
}
