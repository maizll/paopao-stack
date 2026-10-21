<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Admin\UpdateAdminPostRequest;
use App\Models\Board;
use App\Models\Post;
use App\Support\ApiCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Post::query()->with(['author', 'board'])->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        $page = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));
        $page->getCollection()->transform(fn (Post $post) => $post->toListArray());

        return $this->paginated($page);
    }

    public function show(Post $post): JsonResponse
    {
        $post->load(['author', 'board']);

        return $this->ok($post->toDetailArray());
    }

    public function update(UpdateAdminPostRequest $request, Post $post): JsonResponse
    {
        $board = Board::query()->find($request->integer('board_id'));

        if (! $board) {
            throw new ApiException(ApiCode::NOT_FOUND, '版块不存在', 404);
        }

        $previousBoardId = $post->board_id;
        $cover = $request->input('cover');

        $post->title = $request->string('title')->toString();
        $post->content = $request->string('content')->toString();
        $post->cover = $cover === null || $cover === '' ? null : (string) $cover;
        $post->board_id = $board->id;
        $post->save();

        if ($post->status === 'published' && $previousBoardId !== $post->board_id) {
            $this->refreshPublishedCount($previousBoardId);
            $this->refreshPublishedCount($post->board_id);
        }

        $post->load(['author', 'board']);

        return $this->ok($post->toDetailArray());
    }

    public function approve(Post $post): JsonResponse
    {
        if ($post->status === 'published') {
            throw new ApiException(ApiCode::CONFLICT, '帖子已发布', 409);
        }

        $post->status = 'published';
        $post->published_at = now();
        $post->save();

        $board = $post->board;
        if ($board) {
            $board->post_count = Post::query()
                ->where('board_id', $board->id)
                ->where('status', 'published')
                ->count();
            $board->save();
        }

        $post->load(['author', 'board']);

        return $this->ok($post->toDetailArray());
    }

    public function reject(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        if ($post->status === 'rejected') {
            throw new ApiException(ApiCode::CONFLICT, '帖子已拒绝', 409);
        }

        $post->status = 'rejected';
        $post->save();
        $post->load(['author', 'board']);

        return $this->ok($post->toDetailArray());
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return $this->ok(null);
    }

    private function refreshPublishedCount(int $boardId): void
    {
        $board = Board::query()->find($boardId);

        if (! $board) {
            return;
        }

        $board->post_count = Post::query()
            ->where('board_id', $boardId)
            ->where('status', 'published')
            ->count();
        $board->save();
    }
}
