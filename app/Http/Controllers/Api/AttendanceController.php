<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // GET /api/attendance — ?search=, ?user_id=, ?status=, ?date=, ?date_from=, ?date_to=
    public function index(Request $request): JsonResponse
    {
        $records = Attendance::with('user')
            ->search($request->search)
            ->when($request->user_id,  fn($q, $v) => $q->where('user_id', $v))
            ->when($request->status,   fn($q, $v) => $q->where('status', $v))
            ->when($request->date,     fn($q, $v) => $q->whereDate('date', $v))
            ->when($request->date_from,fn($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->date_to,  fn($q, $v) => $q->whereDate('date', '<=', $v))
            ->orderByDesc('date')
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($records, fn($a) => $this->formatAttendance($a));
    }

    // POST /api/attendance — mark attendance (upsert by user+date)
    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $workHours = null;
        if ($request->check_in && $request->check_out) {
            $in  = \Carbon\Carbon::createFromFormat('H:i', $request->check_in);
            $out = \Carbon\Carbon::createFromFormat('H:i', $request->check_out);
            $workHours = round($out->diffInMinutes($in) / 60, 2);
        }

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $request->user_id, 'date' => $request->date],
            array_merge($request->validated(), [
                'work_hours' => $request->work_hours ?? $workHours,
                'marked_by'  => $request->user()->id,
            ])
        );

        return $this->success($this->formatAttendance($attendance->load('user')), 'Attendance recorded.');
    }

    // GET /api/attendance/{attendance}
    public function show(Attendance $attendance): JsonResponse
    {
        $attendance->load('user');
        return $this->success($this->formatAttendance($attendance), 'Attendance retrieved.');
    }

    // PUT /api/attendance/{attendance}
    public function update(StoreAttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        $attendance->update($request->validated());
        return $this->success($this->formatAttendance($attendance->load('user')), 'Attendance updated.');
    }

    // GET /api/attendance/summary — monthly summary per employee
    public function summary(Request $request): JsonResponse
    {
        $request->validate(['month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/']]);

        $summary = Attendance::with('user')
            ->where('date', 'like', $request->month . '%')
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->get()
            ->groupBy('user_id')
            ->map(fn($records) => [
                'employee'       => ['id' => $records->first()->user->id, 'name' => $records->first()->user->name, 'employee_id' => $records->first()->user->employee_id],
                'present'        => $records->where('status', 'present')->count(),
                'absent'         => $records->where('status', 'absent')->count(),
                'late'           => $records->where('status', 'late')->count(),
                'half_day'       => $records->where('status', 'half_day')->count(),
                'on_leave'       => $records->where('status', 'on_leave')->count(),
                'total_work_hrs' => round($records->sum('work_hours'), 2),
                'overtime_hrs'   => round($records->sum('overtime_hours'), 2),
            ])
            ->values();

        return $this->success($summary, 'Attendance summary retrieved.');
    }

    private function formatAttendance(Attendance $a): array
    {
        return [
            'id'             => $a->id,
            'date'           => $a->date?->toDateString(),
            'check_in'       => $a->check_in,
            'check_out'      => $a->check_out,
            'work_hours'     => $a->work_hours,
            'overtime_hours' => $a->overtime_hours,
            'status'         => $a->status,
            'notes'          => $a->notes,
            'employee'       => $a->user ? ['id' => $a->user->id, 'name' => $a->user->name, 'employee_id' => $a->user->employee_id] : null,
        ];
    }
}
