<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'tenant_id',
    'board_id',
    'user_id',
    'app_id',
    'title',
    'content',
    'cover',
    'status',
    'view_count',
    'like_count',
    'comment_count',
    'favorite_count',
    'published_at',
])]
class Post extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class, 'app_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toListArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => mb_substr(trim(strip_tags($this->content)), 0, 80),
            'cover' => $this->cover,
            'author' => $this->author?->toPublicArray(),
            'board' => $this->board ? [
                'id' => $this->board->id,
                'name' => $this->board->name,
                'slug' => $this->board->slug,
            ] : null,
            'app_id' => $this->app_id,
            'stats' => $this->stats(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetailArray(): array
    {
        return [
            ...$this->toListArray(),
            'content' => $this->content,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        return [
            'view' => $this->view_count,
            'like' => $this->like_count,
            'comment' => $this->comment_count,
            'favorite' => $this->favorite_count,
        ];
    }
}
