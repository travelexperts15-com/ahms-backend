<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends BaseController
{
    // GET /api/audit-logs — ?search=, ?event=, ?user_id=, ?date_from=, ?date_to=, ?per_page=
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::with('user')
            ->when($request->search,    fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('event', 'like', "%{$v}%")
                  ->orWhere('description', 'like', "%{$v}%");
            }))
            ->when($request->event,     fn($q, $v) => $q->where('event', $v))
            ->when($request->user_id,   fn($q, $v) => $q->where('user_id', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to,   fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($logs, fn($log) => [
            'id'          => $log->id,
            'event'       => $log->event,
            'description' => $log->description,
            'ip_address'  => $log->ip_address,
            'user_agent'  => $log->user_agent,
            'properties'  => $log->properties,
            'created_at'  => $log->created_at?->toDateTimeString(),
            'user'        => $log->user ? ['id' => $log->user->id, 'name' => $log->user->name, 'employee_id' => $log->user->employee_id] : null,
        ]);
    }

    // GET /api/audit-logs/events — distinct event types for filter dropdowns
    public function events(): JsonResponse
    {
        $events = ActivityLog::select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        return $this->success($events, 'Audit event types retrieved.');
    }
}
