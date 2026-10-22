<?php

namespace App\Http\Controllers\Api\V1\Comments;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Comments\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\Comments\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends ApiController
{
    public function __construct(private readonly CommentService $comments) {}

    public function index(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();
        $visible = $post->status === 'published' || ($user && $user->id === $post->user_id);
        if (! $visible) {
            abort(404);
        }

        $query = Comment::query()
            ->with('author')
            ->where('post_id', $post->id)
            ->where(function ($builder) use ($user): void {
                $builder->where('status', 'published');
                if ($user) {
                    $builder->orWhere('user_id', $user->id);
                }
            })
            ->orderBy('id');

        $page = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));
        $page->getCollection()->transform(fn (Comment $comment) => $comment->toApiArray());

        return $this->paginated($page);
    }

    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $comment = $this->comments->store(
            $request->user(),
            $post,
            $request->string('content')->toString(),
            $request->filled('parent_id') ? $request->integer('parent_id') : null,
            $request->filled('reply_to_user_id') ? $request->integer('reply_to_user_id') : null,
        );

        return $this->ok($comment->toApiArray());
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);
        $this->comments->remove($comment);

        return $this->ok(null);
    }

    public function replies(): JsonResponse
    {
        return $this->phaseTwo('楼中楼回复');
    }
}
