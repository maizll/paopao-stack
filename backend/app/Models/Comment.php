<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'tenant_id',
    'post_id',
    'user_id',
    'parent_id',
    'reply_to_user_id',
    'content',
    'status',
    'like_count',
])]
class Comment extends Model
{
    use BelongsToTenant, SoftDeletes;

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'content' => $this->content,
            'author' => $this->author?->toPublicArray(),
            'parent_id' => $this->parent_id,
            'reply_to_user_id' => $this->reply_to_user_id,
            'like_count' => $this->like_count,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'post' => $this->relationLoaded('post') && $this->post ? [
                'id' => $this->post->id,
                'title' => $this->post->title,
            ] : null,
        ];
    }
}
