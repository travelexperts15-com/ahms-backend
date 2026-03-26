<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Admission;
use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends BaseController
{
    // GET /api/reports/patients — ?date_from=, ?date_to=, ?gender=, ?blood_group=, ?status=
    public function patients(Request $request): JsonResponse
    {
        $query = Patient::query()
            ->when($request->date_from,  fn($q, $v) => $q->whereDate('registration_date', '>=', $v))
            ->when($request->date_to,    fn($q, $v) => $q->whereDate('registration_date', '<=', $v))
            ->when($request->gender,     fn($q, $v) => $q->where('gender', $v))
            ->when($request->blood_group,fn($q, $v) => $q->where('blood_group', $v))
            ->when($request->status,     fn($q, $v) => $q->where('status', $v));

        return $this->success([
            'total'      => $query->count(),
            'by_gender'  => $query->clone()->groupBy('gender')->selectRaw('gender, count(*) as count')->pluck('count', 'gender'),
            'by_status'  => $query->clone()->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_month'   => $query->clone()
                ->selectRaw('DATE_FORMAT(registration_date, "%Y-%m") as month, count(*) as count')
                ->groupBy('month')->orderBy('month')
                ->pluck('count', 'month'),
        ], 'Patient report retrieved.');
    }

    // GET /api/reports/appointments — ?date_from=, ?date_to=, ?doctor_id=, ?department_id=
    public function appointments(Request $request): JsonResponse
    {
        $query = Appointment::query()
            ->when($request->date_from,     fn($q, $v) => $q->whereDate('appointment_date', '>=', $v))
            ->when($request->date_to,       fn($q, $v) => $q->whereDate('appointment_date', '<=', $v))
            ->when($request->doctor_id,     fn($q, $v) => $q->where('doctor_id', $v))
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v));

        return $this->success([
            'total'       => $query->count(),
            'by_status'   => $query->clone()->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_type'     => $query->clone()->groupBy('type')->selectRaw('type, count(*) as count')->pluck('count', 'type'),
            'by_month'    => $query->clone()
                ->selectRaw('DATE_FORMAT(appointment_date, "%Y-%m") as month, count(*) as count')
                ->groupBy('month')->orderBy('month')
                ->pluck('count', 'month'),
        ], 'Appointment report retrieved.');
    }

    // GET /api/reports/admissions — ?date_from=, ?date_to=, ?department_id=, ?status=
    public function admissions(Request $request): JsonResponse
    {
        $query = Admission::query()
            ->when($request->date_from,     fn($q, $v) => $q->whereDate('admission_date', '>=', $v))
            ->when($request->date_to,       fn($q, $v) => $q->whereDate('admission_date', '<=', $v))
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v))
            ->when($request->status,        fn($q, $v) => $q->where('status', $v));

        return $this->success([
            'total'              => $query->count(),
            'currently_admitted' => Admission::where('status', 'admitted')->count(),
            'by_status'          => $query->clone()->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_admission_type'  => $query->clone()->groupBy('admission_type')->selectRaw('admission_type, count(*) as count')->pluck('count', 'admission_type'),
            'avg_length_of_stay' => round(
                Admission::whereNotNull('discharge_date')
                    ->selectRaw('AVG(DATEDIFF(discharge_date, admission_date)) as avg_days')
                    ->value('avg_days') ?? 0,
                1
            ),
        ], 'Admission report retrieved.');
    }

    // GET /api/reports/revenue — ?date_from=, ?date_to=
    public function revenue(Request $request): JsonResponse
    {
        $invoiceQuery = Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->when($request->date_from, fn($q, $v) => $q->whereDate('invoice_date', '>=', $v))
            ->when($request->date_to,   fn($q, $v) => $q->whereDate('invoice_date', '<=', $v));

        $paymentQuery = Payment::query()
            ->when($request->date_from, fn($q, $v) => $q->whereDate('payment_date', '>=', $v))
            ->when($request->date_to,   fn($q, $v) => $q->whereDate('payment_date', '<=', $v));

        return $this->success([
            'total_billed'   => $invoiceQuery->clone()->sum('total_amount'),
            'total_collected'=> $invoiceQuery->clone()->sum('paid_amount'),
            'total_balance'  => $invoiceQuery->clone()->sum('balance'),
            'by_status'      => $invoiceQuery->clone()->groupBy('status')->selectRaw('status, count(*) as count, sum(total_amount) as total')->get()->mapWithKeys(fn($r) => [$r->status => ['count' => $r->count, 'total' => $r->total]]),
            'by_method'      => $paymentQuery->clone()->groupBy('payment_method')->selectRaw('payment_method, sum(amount) as total')->pluck('total', 'payment_method'),
            'by_month'       => $invoiceQuery->clone()
                ->selectRaw('DATE_FORMAT(invoice_date, "%Y-%m") as month, sum(total_amount) as billed, sum(paid_amount) as collected')
                ->groupBy('month')->orderBy('month')
                ->get()->mapWithKeys(fn($r) => [$r->month => ['billed' => $r->billed, 'collected' => $r->collected]]),
        ], 'Revenue report retrieved.');
    }

    // GET /api/reports/lab — ?date_from=, ?date_to=
    public function lab(Request $request): JsonResponse
    {
        $query = LabOrder::query()
            ->when($request->date_from, fn($q, $v) => $q->whereDate('ordered_date', '>=', $v))
            ->when($request->date_to,   fn($q, $v) => $q->whereDate('ordered_date', '<=', $v));

        return $this->success([
            'total'     => $query->count(),
            'by_status' => $query->clone()->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_month'  => $query->clone()
                ->selectRaw('DATE_FORMAT(ordered_date, "%Y-%m") as month, count(*) as count')
                ->groupBy('month')->orderBy('month')
                ->pluck('count', 'month'),
        ], 'Lab report retrieved.');
    }

    // GET /api/reports/attendance — ?user_id=, ?month= (YYYY-MM)
    public function attendance(Request $request): JsonResponse
    {
        $request->validate(['month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/']]);

        $records = Attendance::where('date', 'like', $request->month . '%')
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->get();

        return $this->success([
            'month'          => $request->month,
            'total_records'  => $records->count(),
            'by_status'      => $records->groupBy('status')->map->count(),
            'total_work_hrs' => round($records->sum('work_hours'), 2),
            'overtime_hrs'   => round($records->sum('overtime_hours'), 2),
        ], 'Attendance report retrieved.');
    }
}
