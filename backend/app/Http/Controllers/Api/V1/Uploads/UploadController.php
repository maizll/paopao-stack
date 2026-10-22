<?php

namespace App\Http\Controllers\Api\V1\Uploads;

use App\Http\Controllers\Api\V1\ApiController;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ], [
            'file.required' => '请选择要上传的图片。',
            'file.image' => '只能上传图片。',
            'file.mimes' => '图片格式只支持 JPG、PNG、WEBP、GIF。',
            'file.max' => '图片不能超过 5 MB。',
        ], [
            'file' => '图片',
        ]);

        $tenantId = app(TenantContext::class)->id();
        $path = $request->file('file')->store('uploads/'.$tenantId, 'public');

        return $this->ok([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
        ]);
    }
}
