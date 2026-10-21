<?php

namespace App\Http\Controllers\Api\V1\Boards;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Board;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $boards = Board::query()
            ->where('status', 'published')
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (Board $board) => $board->toPublicArray());

        return $this->ok($boards->all());
    }

    public function show(Board $board): JsonResponse
    {
        if ($board->status !== 'published') {
            abort(404);
        }

        return $this->ok($board->toPublicArray());
    }

    public function posts(Request $request, Board $board): JsonResponse
    {
        if ($board->status !== 'published') {
            abort(404);
        }

        $page = Post::query()
            ->with(['author', 'board'])
            ->where('board_id', $board->id)
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request), ['*'], 'page', $this->page($request));

        $page->getCollection()->transform(fn (Post $post) => $post->toListArray());

        return $this->paginated($page);
    }

    public function join(): JsonResponse
    {
        return $this->phaseTwo('加入圈子');
    }

    public function leave(): JsonResponse
    {
        return $this->phaseTwo('退出圈子');
    }
}
