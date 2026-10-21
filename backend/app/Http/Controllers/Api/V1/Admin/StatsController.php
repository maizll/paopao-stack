<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\JsonResponse;

class StatsController extends ApiController
{
    public function overview(): JsonResponse
    {
        return $this->phaseTwo('数据概览');
    }
}
