<?php

namespace App\Events;

class CommentVisibilityChanged
{
    public function __construct(
        public readonly int $commentId,
        public readonly int $postId,
        public readonly int $countDelta,
        public readonly ?string $notice,
        public readonly ?string $reason = null,
    ) {}
}
