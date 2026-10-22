<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\OpsBanner;
use App\Models\OpsGridItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpsController extends ApiController
{
    public function banners(): JsonResponse
    {
        $items = OpsBanner::query()->orderBy('sort')->orderBy('id')->get()
            ->map(fn (OpsBanner $banner) => $banner->toPublicArray());

        return $this->ok($items->all());
    }

    public function storeBanner(Request $request): JsonResponse
    {
        $banner = OpsBanner::query()->create($this->bannerFields($request));

        return $this->ok($banner->toPublicArray());
    }

    public function updateBanner(Request $request, OpsBanner $banner): JsonResponse
    {
        $banner->fill($this->bannerFields($request));
        $banner->save();

        return $this->ok($banner->toPublicArray());
    }

    public function destroyBanner(OpsBanner $banner): JsonResponse
    {
        $banner->delete();

        return $this->ok(null);
    }

    public function grid(): JsonResponse
    {
        $items = OpsGridItem::query()->orderBy('sort')->orderBy('id')->get()
            ->map(fn (OpsGridItem $item) => $item->toPublicArray());

        return $this->ok($items->all());
    }

    public function storeGrid(Request $request): JsonResponse
    {
        $item = OpsGridItem::query()->create($this->gridFields($request));

        return $this->ok($item->toPublicArray());
    }

    public function updateGrid(Request $request, OpsGridItem $gridItem): JsonResponse
    {
        $gridItem->fill($this->gridFields($request));
        $gridItem->save();

        return $this->ok($gridItem->toPublicArray());
    }

    public function destroyGrid(OpsGridItem $gridItem): JsonResponse
    {
        $gridItem->delete();

        return $this->ok(null);
    }

    /**
     * @return array<string, mixed>
     */
    private function bannerFields(Request $request): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:40'],
            'image' => ['required', 'string', 'max:255'],
            'link_type' => ['required', 'in:url,app,post,board'],
            'link_value' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', 'in:draft,published'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
        ], [
            'image.required' => '请上传或填写 Banner 图片。',
            'link_type.in' => '链接类型不正确。',
            'status.in' => '状态只能是草稿或已发布。',
        ], [
            'title' => '标题',
            'image' => '图片',
            'link_type' => '链接类型',
            'link_value' => '链接',
            'sort' => '排序',
            'status' => '状态',
        ]);

        return [
            'title' => ($data['title'] ?? '') !== '' ? $data['title'] : null,
            'image' => $data['image'],
            'link_type' => $data['link_type'],
            'link_value' => ($data['link_value'] ?? '') !== '' ? $data['link_value'] : null,
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => $data['status'],
            'start_at' => $data['start_at'] ?? null,
            'end_at' => $data['end_at'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gridFields(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:12'],
            'icon' => ['nullable', 'string', 'max:255'],
            'link_type' => ['required', 'in:url,app,post,board'],
            'link_value' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:8'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', 'in:draft,published'],
        ], [
            'title.required' => '请填写宫格标题。',
            'title.max' => '宫格标题不能超过 12 字。',
            'link_type.in' => '链接类型不正确。',
            'status.in' => '状态只能是草稿或已发布。',
        ], [
            'title' => '标题',
            'icon' => '图标',
            'link_type' => '链接类型',
            'link_value' => '链接',
            'badge' => '角标',
            'sort' => '排序',
            'status' => '状态',
        ]);

        return [
            'title' => $data['title'],
            'icon' => ($data['icon'] ?? '') !== '' ? $data['icon'] : null,
            'link_type' => $data['link_type'],
            'link_value' => ($data['link_value'] ?? '') !== '' ? $data['link_value'] : null,
            'badge' => ($data['badge'] ?? '') !== '' ? $data['badge'] : null,
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => $data['status'],
        ];
    }
}
