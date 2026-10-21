<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Software;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $page = Software::query()
            ->with([
                'category',
                'versions' => fn ($q) => $q->orderByDesc('version_code'),
            ])
            ->orderByDesc('id')
            ->paginate($this->perPage($request), ['*'], 'page', $this->page($request));

        $page->getCollection()->transform(fn (Software $app) => $app->toListArray());

        return $this->paginated($page);
    }

    public function store(): JsonResponse
    {
        return $this->notImplemented('软件创建');
    }

    public function update(): JsonResponse
    {
        return $this->notImplemented('软件编辑与上下架');
    }
}
