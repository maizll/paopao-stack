<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Comment;
use App\Services\Comments\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends ApiController
{
    public function __construct(private readonly CommentService $comments) {}

    public function index(Request $request): JsonResponse
    {
        $query = Comment::query()->with(['author', 'post'])->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        $page = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));
        $page->getCollection()->transform(fn (Comment $comment) => $comment->toApiArray());

        return $this->paginated($page);
    }

    public function show(Comment $comment): JsonResponse
    {
        $comment->load(['author', 'post']);

        return $this->ok($comment->toApiArray());
    }

    public function approve(Comment $comment): JsonResponse
    {
        return $this->ok($this->comments->publish($comment)->toApiArray());
    }

    public function reject(Request $request, Comment $comment): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        return $this->ok($this->comments->reject($comment, $data['reason'] ?? null)->toApiArray());
    }

    public function hide(Comment $comment): JsonResponse
    {
        return $this->ok($this->comments->hide($comment)->toApiArray());
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->comments->remove($comment);

        return $this->ok(null);
    }
}
