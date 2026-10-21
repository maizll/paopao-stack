<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\JsonResponse;

class ReportController extends ApiController
{
    public function store(): JsonResponse
    {
        return $this->notImplemented('提交举报');
    }

    public function mine(): JsonResponse
    {
        return $this->notImplemented('我的举报');
    }

    public function show(): JsonResponse
    {
        return $this->phaseTwo('举报进度');
    }
}
