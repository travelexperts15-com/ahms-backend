<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Payroll\StorePayrollRequest;
use App\Models\Payroll;
use App\Models\StaffProfile;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // GET /api/payroll — ?search=, ?month=, ?status=, ?user_id=
    public function index(Request $request): JsonResponse
    {
        $payrolls = Payroll::with('user')
            ->search($request->search)
            ->when($request->month,   fn($q, $v) => $q->where('month', $v))
            ->when($request->status,  fn($q, $v) => $q->where('status', $v))
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->orderByDesc('month')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($payrolls, fn($p) => $this->formatPayroll($p));
    }

    // POST /api/payroll — generate payroll for one employee
    public function store(StorePayrollRequest $request): JsonResponse
    {
        $exists = Payroll::where('user_id', $request->user_id)->where('month', $request->month)->exists();
        if ($exists) {
            return $this->error("Payroll for this employee already exists for {$request->month}.", 422);
        }

        $last   = Payroll::whereNotNull('payroll_number')->max('payroll_number');
        $number = $last ? 'PAY-SAL-' . str_pad((int) preg_replace('/\D/', '', $last) + 1, 4, '0', STR_PAD_LEFT) : 'PAY-SAL-0001';

        $gross      = ($request->basic_salary ?? 0) + ($request->allowances ?? 0) + ($request->overtime_pay ?? 0);
        $netSalary  = $gross - ($request->deductions ?? 0) - ($request->tax ?? 0);

        $payroll = Payroll::create(array_merge($request->validated(), [
            'payroll_number' => $number,
            'net_salary'     => max(0, $netSalary),
            'created_by'     => $request->user()->id,
        ]));

        $this->audit->log(
            event:       'payroll.created',
            description: "Payroll created: {$payroll->payroll_number} for user #{$request->user_id} ({$request->month})",
            userId:      $request->user()->id,
            properties:  ['payroll_id' => $payroll->id],
        );

        return $this->created($this->formatPayroll($payroll->load('user')), 'Payroll created.');
    }

    // GET /api/payroll/{payroll}
    public function show(Payroll $payroll): JsonResponse
    {
        $payroll->load(['user', 'approvedBy']);
        return $this->success($this->formatPayroll($payroll), 'Payroll retrieved.');
    }

    // PATCH /api/payroll/{payroll}/approve
    public function approve(Request $request, Payroll $payroll): JsonResponse
    {
        if ($payroll->status !== 'draft') {
            return $this->error("Cannot approve a {$payroll->status} payroll.", 422);
        }

        $payroll->update(['status' => 'approved', 'approved_by' => $request->user()->id]);

        $this->audit->log(
            event:       'payroll.approved',
            description: "Payroll approved: {$payroll->payroll_number}",
            userId:      $request->user()->id,
        );

        return $this->success(['status' => 'approved'], 'Payroll approved.');
    }

    // PATCH /api/payroll/{payroll}/mark-paid
    public function markPaid(Request $request, Payroll $payroll): JsonResponse
    {
        if ($payroll->status !== 'approved') {
            return $this->error('Payroll must be approved before marking as paid.', 422);
        }

        $request->validate([
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['sometimes', 'in:cash,bank_transfer,mobile_money'],
        ]);

        $payroll->update([
            'status'         => 'paid',
            'payment_date'   => $request->payment_date,
            'payment_method' => $request->payment_method ?? $payroll->payment_method,
        ]);

        $this->audit->log(
            event:       'payroll.paid',
            description: "Payroll marked paid: {$payroll->payroll_number}",
            userId:      $request->user()->id,
        );

        return $this->success(['status' => 'paid'], 'Payroll marked as paid.');
    }

    // POST /api/payroll/bulk-generate — generate payroll for all staff in a month
    public function bulkGenerate(Request $request): JsonResponse
    {
        $request->validate(['month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/']]);

        $generated = 0;
        $skipped   = 0;

        StaffProfile::with('user')->chunk(100, function ($profiles) use ($request, &$generated, &$skipped) {
            foreach ($profiles as $profile) {
                $exists = Payroll::where('user_id', $profile->user_id)->where('month', $request->month)->exists();
                if ($exists) { $skipped++; continue; }

                $last   = Payroll::whereNotNull('payroll_number')->max('payroll_number');
                $number = $last ? 'PAY-SAL-' . str_pad((int) preg_replace('/\D/', '', $last) + 1, 4, '0', STR_PAD_LEFT) : 'PAY-SAL-0001';

                Payroll::create([
                    'user_id'        => $profile->user_id,
                    'payroll_number' => $number,
                    'month'          => $request->month,
                    'basic_salary'   => $profile->basic_salary,
                    'net_salary'     => $profile->basic_salary,
                    'created_by'     => $request->user()->id,
                ]);

                $generated++;
            }
        });

        return $this->success(
            ['generated' => $generated, 'skipped' => $skipped],
            "Payroll generated for {$generated} employees. {$skipped} already existed."
        );
    }

    private function formatPayroll(Payroll $p): array
    {
        return [
            'id'             => $p->id,
            'payroll_number' => $p->payroll_number,
            'month'          => $p->month,
            'basic_salary'   => $p->basic_salary,
            'allowances'     => $p->allowances,
            'overtime_pay'   => $p->overtime_pay,
            'deductions'     => $p->deductions,
            'tax'            => $p->tax,
            'net_salary'     => $p->net_salary,
            'payment_date'   => $p->payment_date?->toDateString(),
            'payment_method' => $p->payment_method,
            'status'         => $p->status,
            'notes'          => $p->notes,
            'employee'       => $p->user ? ['id' => $p->user->id, 'name' => $p->user->name, 'employee_id' => $p->user->employee_id] : null,
            'approved_by'    => $p->approvedBy?->name,
            'created_at'     => $p->created_at?->toDateTimeString(),
        ];
    }
}
