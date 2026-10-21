<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\JsonResponse;

class ReportController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->notImplemented('举报队列');
    }

    public function resolve(): JsonResponse
    {
        return $this->notImplemented('举报处理');
    }
}
