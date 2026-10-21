<?php

namespace App\Http\Controllers\Api\V1\Interactions;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\JsonResponse;

class InteractionController extends ApiController
{
    public function like(): JsonResponse
    {
        return $this->notImplemented('点赞');
    }

    public function unlike(): JsonResponse
    {
        return $this->notImplemented('取消赞');
    }

    public function favorite(): JsonResponse
    {
        return $this->notImplemented('收藏');
    }

    public function unfavorite(): JsonResponse
    {
        return $this->notImplemented('取消收藏');
    }

    public function favorites(): JsonResponse
    {
        return $this->notImplemented('收藏列表');
    }

    public function status(): JsonResponse
    {
        return $this->notImplemented('赞藏状态');
    }

    public function reaction(): JsonResponse
    {
        return $this->phaseTwo('表情回应');
    }
}
