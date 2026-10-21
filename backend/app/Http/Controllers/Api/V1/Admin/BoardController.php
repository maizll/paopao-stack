<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Admin\StoreBoardRequest;
use App\Models\Board;
use App\Support\ApiCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends ApiController
{
    public function index(): JsonResponse
    {
        $items = Board::query()->orderBy('sort')->orderBy('id')->get()
            ->map(fn (Board $board) => $board->toPublicArray());

        return $this->ok($items->all());
    }

    public function store(StoreBoardRequest $request): JsonResponse
    {
        if (Board::query()->where('slug', $request->string('slug')->toString())->exists()) {
            throw new ApiException(ApiCode::CONFLICT, '版块标识已存在', 409);
        }

        $board = Board::query()->create([
            'name' => $request->string('name')->toString(),
            'slug' => $request->string('slug')->toString(),
            'description' => $request->input('description'),
            'icon' => $request->input('icon'),
            'parent_id' => $request->input('parent_id'),
            'sort' => $request->integer('sort'),
            'status' => $request->input('status', 'published'),
        ]);

        return $this->ok($board->toPublicArray());
    }

    public function update(Request $request, Board $board): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:40'],
            'description' => ['sometimes', 'nullable', 'string', 'max:200'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:published,hidden'],
        ]);

        $board->fill($data);
        $board->save();

        return $this->ok($board->toPublicArray());
    }

    public function destroy(Board $board): JsonResponse
    {
        if ($board->posts()->exists()) {
            throw new ApiException(ApiCode::BUSINESS, '版块下仍有帖子，不能删除', 422);
        }

        $board->delete();

        return $this->ok(null);
    }
}
