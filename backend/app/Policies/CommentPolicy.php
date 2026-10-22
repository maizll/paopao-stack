<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use App\Support\UserRole;

class CommentPolicy
{
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id
            || in_array($user->role, [UserRole::TENANT_ADMIN, UserRole::PLATFORM_SUPER_ADMIN], true);
    }
}
