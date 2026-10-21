<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\JsonResponse;

class AppVersionController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->notImplemented('版本列表管理');
    }

    public function store(): JsonResponse
    {
        return $this->notImplemented('版本上传');
    }

    public function update(): JsonResponse
    {
        return $this->notImplemented('版本发布');
    }
}
