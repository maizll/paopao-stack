<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\OpsBanner;
use App\Models\OpsGridItem;
use Illuminate\Http\JsonResponse;

class OpsController extends ApiController
{
    public function banners(): JsonResponse
    {
        $items = OpsBanner::query()->orderBy('sort')->orderBy('id')->get()
            ->map(fn (OpsBanner $banner) => $banner->toPublicArray());

        return $this->ok($items->all());
    }

    public function storeBanner(): JsonResponse
    {
        return $this->notImplemented('Banner 写入');
    }

    public function grid(): JsonResponse
    {
        $items = OpsGridItem::query()->orderBy('sort')->orderBy('id')->get()
            ->map(fn (OpsGridItem $item) => $item->toPublicArray());

        return $this->ok($items->all());
    }

    public function storeGrid(): JsonResponse
    {
        return $this->notImplemented('九宫格写入');
    }
}
