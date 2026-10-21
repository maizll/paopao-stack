<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Users\UpdateProfileRequest;
use App\Models\Post;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function show(int $id): JsonResponse
    {
        $user = $this->findInTenant($id);

        return $this->ok($user->toPublicArray());
    }

    public function updateMe(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['nickname', 'bio', 'gender']));
        $user->save();

        return $this->ok($user->toMeArray());
    }

    public function avatar(): JsonResponse
    {
        return $this->notImplemented('头像上传');
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->ok([
            'post_count' => Post::query()->where('user_id', $user->id)->count(),
            'like_count' => 0,
            'favorite_count' => 0,
        ]);
    }

    public function posts(Request $request, int $id): JsonResponse
    {
        $user = $this->findInTenant($id);
        $viewer = $request->user();

        $query = Post::query()
            ->with(['author', 'board'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if (! $viewer || $viewer->id !== $user->id) {
            $query->where('status', 'published');
        }

        $page = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));
        $page->getCollection()->transform(fn (Post $post) => $post->toListArray());

        return $this->paginated($page);
    }

    public function follow(): JsonResponse
    {
        return $this->phaseTwo('关注');
    }

    public function unfollow(): JsonResponse
    {
        return $this->phaseTwo('取消关注');
    }

    private function findInTenant(int $id): User
    {
        $tenantId = app(TenantContext::class)->id();

        return User::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->findOrFail($id);
    }
}
