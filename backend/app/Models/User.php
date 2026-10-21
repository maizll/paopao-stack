<?php

namespace App\Models;

use App\Support\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'tenant_id',
    'name',
    'nickname',
    'email',
    'phone',
    'password',
    'avatar',
    'bio',
    'gender',
    'role',
    'status',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->nickname) {
                $user->name = $user->nickname;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return list<string>
     */
    public function permissionNames(): array
    {
        return match ($this->role) {
            UserRole::PLATFORM_SUPER_ADMIN => ['*'],
            UserRole::TENANT_ADMIN => [
                'users.manage',
                'content.moderate',
                'boards.manage',
                'store.manage',
                'ops.manage',
                'reports.handle',
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'nickname' => $this->nickname,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAuthArray(): array
    {
        return [
            'id' => $this->id,
            'nickname' => $this->nickname,
            'avatar' => $this->avatar,
            'roles' => [$this->role],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toMeArray(): array
    {
        return [
            ...$this->toAuthArray(),
            'bio' => $this->bio,
            'gender' => $this->gender,
            'status' => $this->status,
            'tenant_id' => $this->tenant_id,
            'permissions' => $this->permissionNames(),
        ];
    }
}
