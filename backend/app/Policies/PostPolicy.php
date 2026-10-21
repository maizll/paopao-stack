<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use App\Support\UserRole;

class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id && $post->status === 'pending';
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id
            || in_array($user->role, [UserRole::TENANT_ADMIN, UserRole::PLATFORM_SUPER_ADMIN], true);
    }
}
