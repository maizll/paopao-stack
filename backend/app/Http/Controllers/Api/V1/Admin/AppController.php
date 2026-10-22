<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AppCategory;
use App\Models\Software;
use App\Support\ApiCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AppController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Software::query()
            ->with([
                'category',
                'versions' => fn ($q) => $q->orderByDesc('version_code'),
            ])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        $page = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));
        $page->getCollection()->transform(fn (Software $app) => $app->toListArray());

        return $this->paginated($page);
    }

    public function show(Software $app): JsonResponse
    {
        $app->load([
            'category',
            'versions' => fn ($q) => $q->orderByDesc('version_code'),
        ]);

        return $this->ok($app->toDetailArray());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->fields($request);
        $app = Software::query()->create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
            'status' => 'draft',
            'download_count' => 0,
        ]);
        $app->load('category');

        return $this->ok($app->toDetailArray());
    }

    public function update(Request $request, Software $app): JsonResponse
    {
        $app->fill($this->fields($request));
        $app->save();
        $app->load([
            'category',
            'versions' => fn ($q) => $q->orderByDesc('version_code'),
        ]);

        return $this->ok($app->toDetailArray());
    }

    public function approve(Software $app): JsonResponse
    {
        if ($app->status === 'published') {
            throw new ApiException(ApiCode::CONFLICT, '软件已发布', 409);
        }

        $app->status = 'published';
        $app->save();
        $app->load([
            'category',
            'versions' => fn ($q) => $q->orderByDesc('version_code'),
        ]);

        return $this->ok($app->toDetailArray());
    }

    public function reject(Request $request, Software $app): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:200'],
        ], [
            'reason.max' => '拒绝原因不能超过 200 字。',
        ], [
            'reason' => '拒绝原因',
        ]);

        if ($app->status === 'rejected') {
            throw new ApiException(ApiCode::CONFLICT, '软件已拒绝', 409);
        }

        $app->status = 'rejected';
        $app->save();
        $app->load([
            'category',
            'versions' => fn ($q) => $q->orderByDesc('version_code'),
        ]);

        return $this->ok($app->toDetailArray());
    }

    /**
     * @return array{name: string, category_id: int|null, icon: string|null, summary: string|null, description: string|null}
     */
    private function fields(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'category_id' => ['nullable', 'integer'],
            'icon' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:4000'],
        ], [
            'name.required' => '请填写软件名称。',
            'name.max' => '软件名称不能超过 40 字。',
            'summary.max' => '简介不能超过 120 字。',
            'description.max' => '介绍不能超过 4000 字。',
            'icon.max' => '图标地址过长。',
        ], [
            'name' => '名称',
            'category_id' => '分类',
            'icon' => '图标',
            'summary' => '简介',
            'description' => '介绍',
        ]);

        $categoryId = $data['category_id'] ?? null;
        if ($categoryId) {
            $category = AppCategory::query()->find($categoryId);
            if (! $category) {
                throw new ApiException(ApiCode::NOT_FOUND, '分类不存在', 404);
            }
        }

        $icon = $data['icon'] ?? null;

        return [
            'name' => $data['name'],
            'category_id' => $categoryId ?: null,
            'icon' => $icon === null || $icon === '' ? null : $icon,
            'summary' => ($data['summary'] ?? '') !== '' ? $data['summary'] : null,
            'description' => ($data['description'] ?? '') !== '' ? $data['description'] : null,
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'app';
        }

        $slug = $base;
        $suffix = 2;
        while (Software::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
