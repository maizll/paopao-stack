<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\JsonResponse;

class NotificationController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->notImplemented('通知列表');
    }

    public function unreadCount(): JsonResponse
    {
        return $this->notImplemented('未读数');
    }

    public function read(): JsonResponse
    {
        return $this->notImplemented('单条已读');
    }

    public function readAll(): JsonResponse
    {
        return $this->notImplemented('全部已读');
    }

    public function device(): JsonResponse
    {
        return $this->phaseTwo('推送设备登记');
    }
}
