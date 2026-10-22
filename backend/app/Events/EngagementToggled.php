<?php

namespace App\Events;

class EngagementToggled
{
    public function __construct(
        public readonly string $action,
        public readonly string $targetType,
        public readonly int $targetId,
        public readonly int $actorId,
    ) {}
}
