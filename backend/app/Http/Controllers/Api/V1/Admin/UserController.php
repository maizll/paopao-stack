<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\User;
use App\Support\ApiCode;
use App\Support\TenantContext;
use App\Support\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app(TenantContext::class)->id();

        $page = User::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->paginate($this->perPage($request), ['*'], 'page', $this->page($request));

        $page->getCollection()->transform(fn (User $user) => [
            ...$user->toPublicArray(),
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
        ]);

        return $this->paginated($page);
    }

    public function ban(Request $request, int $id): JsonResponse
    {
        $user = $this->findMember($id);

        if ($user->id === $request->user()->id || $user->role === UserRole::PLATFORM_SUPER_ADMIN) {
            throw new ApiException(ApiCode::BUSINESS, '不能封禁该账号', 422);
        }

        $user->status = 'banned';
        $user->save();

        return $this->ok(['id' => $user->id, 'status' => $user->status]);
    }

    public function unban(int $id): JsonResponse
    {
        $user = User::query()
            ->where('tenant_id', app(TenantContext::class)->id())
            ->findOrFail($id);

        $user->status = 'active';
        $user->save();

        return $this->ok(['id' => $user->id, 'status' => $user->status]);
    }

    private function findMember(int $id): User
    {
        return User::query()
            ->where('tenant_id', app(TenantContext::class)->id())
            ->findOrFail($id);
    }
}
