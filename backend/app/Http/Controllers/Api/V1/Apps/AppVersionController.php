<?php

namespace App\Http\Controllers\Api\V1\Apps;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AppVersion;
use App\Models\Software;
use Illuminate\Http\JsonResponse;

class AppVersionController extends ApiController
{
    public function index(Software $app): JsonResponse
    {
        $items = $app->versions()
            ->where('status', 'published')
            ->orderByDesc('version_code')
            ->get()
            ->map(fn (AppVersion $version) => $version->toDetailArray());

        return $this->ok($items->all());
    }

    public function latest(Software $app): JsonResponse
    {
        $version = $app->versions()
            ->where('status', 'published')
            ->orderByDesc('version_code')
            ->firstOrFail();

        return $this->ok($version->toDetailArray());
    }

    public function show(AppVersion $version): JsonResponse
    {
        if ($version->status !== 'published') {
            abort(404);
        }

        return $this->ok($version->toDetailArray());
    }

    public function changelog(): JsonResponse
    {
        return $this->phaseTwo('独立更新说明');
    }
}
