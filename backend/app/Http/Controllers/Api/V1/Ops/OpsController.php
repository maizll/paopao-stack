<?php

namespace App\Http\Controllers\Api\V1\Ops;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\OpsBanner;
use App\Models\OpsGridItem;
use Illuminate\Http\JsonResponse;

class OpsController extends ApiController
{
    public function banners(): JsonResponse
    {
        $items = OpsBanner::query()
            ->where('status', 'published')
            ->where(function ($query): void {
                $query->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('end_at')->orWhere('end_at', '>=', now());
            })
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (OpsBanner $banner) => $banner->toPublicArray());

        return $this->ok($items->all());
    }

    public function grid(): JsonResponse
    {
        $items = OpsGridItem::query()
            ->where('status', 'published')
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (OpsGridItem $item) => $item->toPublicArray());

        return $this->ok($items->all());
    }

    public function homeLayout(): JsonResponse
    {
        return $this->phaseTwo('首页区块编排');
    }

    public function popups(): JsonResponse
    {
        return $this->phaseTwo('弹窗运营');
    }
}
