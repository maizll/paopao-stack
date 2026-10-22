<?php

namespace App\Events;

class PostReviewed
{
    public function __construct(
        public readonly int $postId,
        public readonly string $decision,
        public readonly ?string $reason = null,
    ) {}
}
