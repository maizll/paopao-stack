<?php

namespace App\Http\Controllers\Api\V1\Apps;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AppCategory;
use App\Models\Post;
use App\Models\Software;
use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $sort = ListQuery::sort($request, ['new', 'download'], 'new');

        $query = Software::query()
            ->with([
                'category',
                'versions' => fn ($q) => $q->where('status', 'published')->orderByDesc('version_code'),
            ])
            ->where('status', 'published');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%'.$request->query('keyword').'%');
        }

        if ($sort === 'download') {
            $query->orderByDesc('download_count');
        } else {
            $query->orderByDesc('id');
        }

        $page = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));
        $page->getCollection()->transform(fn (Software $app) => $app->toListArray());

        return $this->paginated($page);
    }

    public function show(Software $app): JsonResponse
    {
        if ($app->status !== 'published') {
            abort(404);
        }

        $app->load([
            'category',
            'versions' => fn ($q) => $q->where('status', 'published')->orderByDesc('version_code'),
        ]);

        return $this->ok($app->toDetailArray());
    }

    public function categories(): JsonResponse
    {
        $items = AppCategory::query()->orderBy('sort')->orderBy('id')->get()
            ->map(fn (AppCategory $category) => $category->toPublicArray());

        return $this->ok($items->all());
    }

    public function relatedPosts(Request $request, Software $app): JsonResponse
    {
        $page = Post::query()
            ->with(['author', 'board'])
            ->where('app_id', $app->id)
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request), ['*'], 'page', $this->page($request));

        $page->getCollection()->transform(fn (Post $post) => $post->toListArray());

        return $this->paginated($page);
    }

    public function rating(): JsonResponse
    {
        return $this->phaseTwo('评分');
    }
}
