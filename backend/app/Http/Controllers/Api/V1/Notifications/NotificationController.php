<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\SiteNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = SiteNotification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id');

        if ($request->filled('type')) {
            $query->where('type', (string) $request->query('type'));
        }

        $page = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));
        $page->getCollection()->transform(fn (SiteNotification $notice) => $notice->toApiArray());

        return $this->paginated($page);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = SiteNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return $this->ok(['count' => $count]);
    }

    public function read(Request $request, int $id): JsonResponse
    {
        $notice = SiteNotification::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (! $notice->read_at) {
            $notice->read_at = now();
            $notice->save();
        }

        return $this->ok($notice->toApiArray());
    }

    public function readAll(Request $request): JsonResponse
    {
        $updated = SiteNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->ok(['updated' => $updated]);
    }

    public function device(): JsonResponse
    {
        return $this->phaseTwo('推送设备登记');
    }
}
