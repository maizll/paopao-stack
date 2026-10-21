<?php

namespace App\Http\Controllers\Api\V1\Downloads;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\JsonResponse;

class DownloadController extends ApiController
{
    public function store(): JsonResponse
    {
        return $this->notImplemented('申请下载');
    }

    public function show(): JsonResponse
    {
        return $this->notImplemented('下载单');
    }

    public function complete(): JsonResponse
    {
        return $this->notImplemented('下载完成上报');
    }

    public function mine(): JsonResponse
    {
        return $this->notImplemented('下载历史');
    }

    public function quota(): JsonResponse
    {
        return $this->phaseTwo('下载配额');
    }
}
