<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiCode;
use App\Support\ApiResponse;
use App\Support\ListQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, string $message = 'ok'): JsonResponse
    {
        return ApiResponse::ok($data, $message);
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     */
    protected function paginated(LengthAwarePaginator $paginator): JsonResponse
    {
        return ApiResponse::ok([
            'items' => array_values($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    protected function notImplemented(string $label): JsonResponse
    {
        return ApiResponse::fail(ApiCode::BUSINESS, '骨架占位：'.$label.'尚未实现', 422);
    }

    protected function phaseTwo(string $label): JsonResponse
    {
        return ApiResponse::fail(ApiCode::BUSINESS, '二期预留：'.$label, 422);
    }

    protected function page(Request $request): int
    {
        return ListQuery::page($request);
    }

    protected function perPage(Request $request): int
    {
        return ListQuery::perPage($request);
    }
}
