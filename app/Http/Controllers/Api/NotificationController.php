<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\SystemNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    // GET /api/notifications — authenticated user's notifications
    public function index(Request $request): JsonResponse
    {
        $notifications = SystemNotification::where('user_id', $request->user()->id)
            ->when($request->unread_only, fn($q) => $q->where('is_read', false))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($notifications, fn($n) => [
            'id'         => $n->id,
            'title'      => $n->title,
            'message'    => $n->message,
            'type'       => $n->type,
            'action_url' => $n->action_url,
            'is_read'    => $n->is_read,
            'read_at'    => $n->read_at?->toDateTimeString(),
            'created_at' => $n->created_at?->toDateTimeString(),
        ]);
    }

    // GET /api/notifications/unread-count
    public function unreadCount(Request $request): JsonResponse
    {
        $count = SystemNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return $this->success(['unread_count' => $count], 'Unread count retrieved.');
    }

    // PATCH /api/notifications/{notification}/read
    public function markRead(Request $request, SystemNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->forbidden('Not your notification.');
        }

        $notification->update(['is_read' => true, 'read_at' => now()]);
        return $this->success(null, 'Notification marked as read.');
    }

    // PATCH /api/notifications/mark-all-read
    public function markAllRead(Request $request): JsonResponse
    {
        SystemNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return $this->success(null, 'All notifications marked as read.');
    }

    // DELETE /api/notifications/{notification}
    public function destroy(Request $request, SystemNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->forbidden('Not your notification.');
        }

        $notification->delete();
        return $this->success(null, 'Notification deleted.');
    }
}
