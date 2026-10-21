<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Posts\StorePostRequest;
use App\Http\Requests\Posts\UpdatePostRequest;
use App\Models\Board;
use App\Models\Post;
use App\Models\Software;
use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $sort = ListQuery::sort($request, ['new', 'hot'], 'new');
        $order = ListQuery::order($request);

        $query = Post::query()
            ->with(['author', 'board'])
            ->where('status', 'published');

        if ($request->filled('board_id')) {
            $query->where('board_id', $request->integer('board_id'));
        }

        if ($request->filled('app_id')) {
            $query->where('app_id', $request->integer('app_id'));
        }

        if ($request->filled('keyword')) {
            $keyword = (string) $request->query('keyword');
            $query->where('title', 'like', '%'.$keyword.'%');
        }

        if ($sort === 'hot') {
            $query->orderBy('like_count', $order)->orderByDesc('id');
        } else {
            $query->orderBy('created_at', $order)->orderByDesc('id');
        }

        $page = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));
        $page->getCollection()->transform(fn (Post $post) => $post->toListArray());

        return $this->paginated($page);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();
        $visible = $post->status === 'published'
            || ($user && $user->id === $post->user_id);

        if (! $visible) {
            abort(404);
        }

        $post->load(['author', 'board']);

        return $this->ok($post->toDetailArray());
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $board = Board::query()->where('status', 'published')->findOrFail($request->integer('board_id'));

        if ($request->filled('app_id')) {
            Software::query()->where('status', 'published')->findOrFail($request->integer('app_id'));
        }

        $post = Post::query()->create([
            'board_id' => $board->id,
            'user_id' => $request->user()->id,
            'app_id' => $request->input('app_id'),
            'title' => $request->string('title')->toString(),
            'content' => $request->string('content')->toString(),
            'cover' => $request->input('cover'),
            'status' => 'pending',
        ]);

        $post->load(['author', 'board']);

        return $this->ok($post->toDetailArray());
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $post->fill($request->safe()->only(['title', 'content', 'cover']));
        $post->save();
        $post->load(['author', 'board']);

        return $this->ok($post->toDetailArray());
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);
        $post->delete();

        return $this->ok(null);
    }

    public function pin(): JsonResponse
    {
        return $this->phaseTwo('置顶');
    }

    public function lock(): JsonResponse
    {
        return $this->phaseTwo('锁帖');
    }
}
