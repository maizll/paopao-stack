<?php

namespace App\Http\Controllers\Api\V1\Comments;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\JsonResponse;

class CommentController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->notImplemented('评论列表');
    }

    public function store(): JsonResponse
    {
        return $this->notImplemented('发表评论');
    }

    public function destroy(): JsonResponse
    {
        return $this->notImplemented('删除评论');
    }

    public function replies(): JsonResponse
    {
        return $this->phaseTwo('楼中楼回复');
    }
}
