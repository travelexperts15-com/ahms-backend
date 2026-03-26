<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Models\Leave;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // GET /api/leaves — ?search=, ?user_id=, ?status=, ?leave_type=
    public function index(Request $request): JsonResponse
    {
        $leaves = Leave::with('user')
            ->search($request->search)
            ->when($request->user_id,    fn($q, $v) => $q->where('user_id', $v))
            ->when($request->status,     fn($q, $v) => $q->where('status', $v))
            ->when($request->leave_type, fn($q, $v) => $q->where('leave_type', $v))
            ->orderByDesc('start_date')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($leaves, fn($l) => $this->formatLeave($l));
    }

    // POST /api/leaves — employee applies for leave
    public function store(StoreLeaveRequest $request): JsonResponse
    {
        $start     = \Carbon\Carbon::parse($request->start_date);
        $end       = \Carbon\Carbon::parse($request->end_date);
        $totalDays = $start->diffInDays($end) + 1;

        // Check for overlapping pending/approved leaves
        $overlap = Leave::where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(fn($q) =>
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
            )->exists();

        if ($overlap) {
            return $this->error('You already have a leave request overlapping these dates.', 422);
        }

        $leave = Leave::create([
            'user_id'    => $request->user()->id,
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'total_days' => $totalDays,
            'reason'     => $request->reason,
        ]);

        $this->audit->log(
            event:       'leave.applied',
            description: "Leave applied: {$request->user()->name} — {$request->leave_type} ({$totalDays} days)",
            userId:      $request->user()->id,
            properties:  ['leave_id' => $leave->id],
        );

        return $this->created($this->formatLeave($leave->load('user')), 'Leave application submitted.');
    }

    // GET /api/leaves/{leave}
    public function show(Leave $leave): JsonResponse
    {
        $leave->load(['user', 'approvedBy']);
        return $this->success($this->formatLeave($leave), 'Leave retrieved.');
    }

    // PATCH /api/leaves/{leave}/approve
    public function approve(Request $request, Leave $leave): JsonResponse
    {
        if ($leave->status !== 'pending') {
            return $this->error("Cannot approve a {$leave->status} leave.", 422);
        }

        $leave->update([
            'status'      => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $this->audit->log(
            event:       'leave.approved',
            description: "Leave approved: #{$leave->id} for {$leave->user->name}",
            userId:      $request->user()->id,
        );

        return $this->success(['status' => 'approved'], 'Leave approved.');
    }

    // PATCH /api/leaves/{leave}/reject
    public function reject(Request $request, Leave $leave): JsonResponse
    {
        if ($leave->status !== 'pending') {
            return $this->error("Cannot reject a {$leave->status} leave.", 422);
        }

        $request->validate(['rejection_reason' => ['required', 'string']]);

        $leave->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
        ]);

        return $this->success(['status' => 'rejected'], 'Leave rejected.');
    }

    // PATCH /api/leaves/{leave}/cancel — employee cancels their own pending leave
    public function cancel(Request $request, Leave $leave): JsonResponse
    {
        if ($leave->user_id !== $request->user()->id) {
            return $this->forbidden('You can only cancel your own leave.');
        }

        if ($leave->status !== 'pending') {
            return $this->error("Cannot cancel a {$leave->status} leave.", 422);
        }

        $leave->update(['status' => 'cancelled']);
        return $this->success(['status' => 'cancelled'], 'Leave cancelled.');
    }

    private function formatLeave(Leave $l): array
    {
        return [
            'id'               => $l->id,
            'leave_type'       => $l->leave_type,
            'start_date'       => $l->start_date?->toDateString(),
            'end_date'         => $l->end_date?->toDateString(),
            'total_days'       => $l->total_days,
            'reason'           => $l->reason,
            'rejection_reason' => $l->rejection_reason,
            'status'           => $l->status,
            'approved_at'      => $l->approved_at?->toDateTimeString(),
            'employee'         => $l->user ? ['id' => $l->user->id, 'name' => $l->user->name, 'employee_id' => $l->user->employee_id] : null,
            'approved_by'      => $l->approvedBy?->name,
            'created_at'       => $l->created_at?->toDateTimeString(),
        ];
    }
}
