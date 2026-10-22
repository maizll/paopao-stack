<?php

namespace App\Http\Controllers\Api\V1\Interactions;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Interactions\InteractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InteractionController extends ApiController
{
    public function __construct(private readonly InteractionService $interactions) {}

    public function like(Request $request): JsonResponse
    {
        [$type, $id] = $this->target($request);

        return $this->ok($this->interactions->like($request->user(), $type, $id));
    }

    public function unlike(Request $request): JsonResponse
    {
        [$type, $id] = $this->target($request);

        return $this->ok($this->interactions->unlike($request->user(), $type, $id));
    }

    public function favorite(Request $request): JsonResponse
    {
        [$type, $id] = $this->target($request);

        return $this->ok($this->interactions->favorite($request->user(), $type, $id));
    }

    public function unfavorite(Request $request): JsonResponse
    {
        [$type, $id] = $this->target($request);

        return $this->ok($this->interactions->unfavorite($request->user(), $type, $id));
    }

    public function favorites(Request $request): JsonResponse
    {
        $page = $this->interactions->favorites($request->user(), $this->perPage($request), $this->page($request));

        return $this->paginated($page);
    }

    public function status(Request $request): JsonResponse
    {
        return $this->ok([
            'items' => $this->interactions->status($request->user(), $this->targets($request)),
        ]);
    }

    public function reaction(): JsonResponse
    {
        return $this->phaseTwo('表情回应');
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function target(Request $request): array
    {
        $data = $request->validate([
            'target_type' => ['required', 'in:post,comment,app'],
            'target_id' => ['required', 'integer'],
        ]);

        return [(string) $data['target_type'], (int) $data['target_id']];
    }

    /**
     * @return list<array{0: string, 1: int}>
     */
    private function targets(Request $request): array
    {
        $raw = $request->query('targets', []);
        $parts = is_array($raw) ? $raw : explode(',', (string) $raw);
        $targets = [];

        foreach ($parts as $part) {
            if (! is_string($part) || ! str_contains($part, ':')) {
                continue;
            }
            [$type, $id] = explode(':', $part, 2);
            if (! in_array($type, ['post', 'comment', 'app'], true) || ! ctype_digit($id)) {
                continue;
            }
            $targets[] = [$type, (int) $id];
            if (count($targets) >= 20) {
                break;
            }
        }

        return $targets;
    }
}
