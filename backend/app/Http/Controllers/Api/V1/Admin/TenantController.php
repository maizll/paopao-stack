<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);

        $page = Tenant::query()
            ->orderByDesc('id')
            ->paginate($this->perPage($request), ['*'], 'page', $this->page($request));

        $page->getCollection()->transform(fn (Tenant $tenant) => $tenant->toPublicArray());

        return $this->paginated($page);
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);

        $tenant = Tenant::query()->create([
            'name' => $request->string('name')->toString(),
            'slug' => $request->string('slug')->toString(),
            'domain' => $request->input('domain'),
            'status' => $request->input('status', 'active'),
            'plan_code' => $request->input('plan_code', 'free'),
        ]);

        return $this->ok($tenant->toPublicArray());
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        return $this->ok($tenant->toPublicArray());
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('update', $tenant);

        $tenant->fill($request->safe()->only(['name', 'domain', 'status', 'plan_code']));
        $tenant->save();

        return $this->ok($tenant->toPublicArray());
    }
}
