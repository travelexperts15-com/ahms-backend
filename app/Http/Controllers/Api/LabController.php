<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Lab\StoreLabOrderRequest;
use App\Http\Requests\Lab\StoreLabResultRequest;
use App\Http\Resources\LabOrderResource;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // ── Lab Tests (master list) ───────────────────────────────────────────────

    // GET /api/lab/tests
    public function tests(Request $request): JsonResponse
    {
        $tests = LabTest::search($request->search)
            ->when($request->category, fn($q, $v) => $q->where('category', $v))
            ->when($request->status,   fn($q, $v) => $q->where('status', $v))
            ->orderBy('name')
            ->get();

        return $this->success($tests, 'Lab tests retrieved.');
    }

    // POST /api/lab/tests
    public function storeTest(Request $request): JsonResponse
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:191', 'unique:lab_tests,name'],
            'code'         => ['nullable', 'string', 'max:30', 'unique:lab_tests,code'],
            'category'     => ['nullable', 'string', 'max:100'],
            'description'  => ['nullable', 'string'],
            'sample_type'  => ['nullable', 'string', 'max:100'],
            'normal_range' => ['nullable', 'string', 'max:191'],
            'unit'         => ['nullable', 'string', 'max:50'],
            'price'        => ['nullable', 'numeric', 'min:0'],
            'status'       => ['sometimes', 'in:active,inactive'],
        ]);

        $test = LabTest::create($request->validated());
        return $this->created($test, 'Lab test added.');
    }

    // ── Lab Orders ────────────────────────────────────────────────────────────

    // GET /api/lab/orders — ?search=, ?status=, ?patient_id=, ?doctor_id=, ?date_from=, ?date_to=
    public function orders(Request $request): JsonResponse
    {
        $orders = LabOrder::with(['patient', 'doctor'])
            ->search($request->search)
            ->when($request->status,     fn($q, $v) => $q->where('status', $v))
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->doctor_id,  fn($q, $v) => $q->where('doctor_id', $v))
            ->when($request->date_from,  fn($q, $v) => $q->whereDate('ordered_date', '>=', $v))
            ->when($request->date_to,    fn($q, $v) => $q->whereDate('ordered_date', '<=', $v))
            ->orderByDesc('ordered_date')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($orders, LabOrderResource::class);
    }

    // POST /api/lab/orders — create order + placeholder results for each test
    public function storeOrder(StoreLabOrderRequest $request): JsonResponse
    {
        $order = DB::transaction(function () use ($request) {
            $last   = LabOrder::whereNotNull('order_number')->max('order_number');
            $number = $last ? 'LAB-' . str_pad((int) preg_replace('/\D/', '', $last) + 1, 4, '0', STR_PAD_LEFT) : 'LAB-0001';

            $order = LabOrder::create([
                'order_number'   => $number,
                'patient_id'     => $request->patient_id,
                'doctor_id'      => $request->doctor_id,
                'opd_visit_id'   => $request->opd_visit_id,
                'admission_id'   => $request->admission_id,
                'ordered_date'   => $request->ordered_date,
                'clinical_notes' => $request->clinical_notes,
                'ordered_by'     => $request->user()->id,
            ]);

            // Create a result row for each ordered test (result to be filled later)
            foreach ($request->test_ids as $testId) {
                $test = LabTest::find($testId);
                LabResult::create([
                    'lab_order_id' => $order->id,
                    'lab_test_id'  => $testId,
                    'patient_id'   => $request->patient_id,
                    'normal_range' => $test?->normal_range,
                    'unit'         => $test?->unit,
                ]);
            }

            return $order;
        });

        $order->load(['patient', 'doctor', 'results.labTest']);

        $this->audit->log(
            event:       'lab.order_created',
            description: "Lab order created: {$order->order_number}",
            userId:      $request->user()->id,
            properties:  ['order_id' => $order->id],
        );

        return $this->created(new LabOrderResource($order), 'Lab order created successfully.');
    }

    // GET /api/lab/orders/{order}
    public function showOrder(LabOrder $order): JsonResponse
    {
        $order->load(['patient', 'doctor', 'results.labTest', 'orderedBy']);
        return $this->success(new LabOrderResource($order), 'Lab order retrieved.');
    }

    // POST /api/lab/orders/{order}/results — Enter results for an order
    public function enterResults(StoreLabResultRequest $request, LabOrder $order): JsonResponse
    {
        if ($order->status === 'completed') {
            return $this->error('Results already entered for this order.', 422);
        }

        DB::transaction(function () use ($request, $order) {
            foreach ($request->results as $resultData) {
                LabResult::where('lab_order_id', $order->id)
                    ->where('lab_test_id', $resultData['lab_test_id'])
                    ->update([
                        'result_value' => $resultData['result_value'] ?? null,
                        'unit'         => $resultData['unit'] ?? null,
                        'normal_range' => $resultData['normal_range'] ?? null,
                        'result_flag'  => $resultData['result_flag'] ?? null,
                        'remarks'      => $resultData['remarks'] ?? null,
                        'performed_by' => $request->user()->id,
                        'resulted_at'  => now(),
                    ]);
            }

            $order->update(['status' => 'completed']);
        });

        $order->load(['patient', 'doctor', 'results.labTest']);

        $this->audit->log(
            event:       'lab.results_entered',
            description: "Lab results entered: {$order->order_number}",
            userId:      $request->user()->id,
        );

        return $this->success(new LabOrderResource($order), 'Lab results entered successfully.');
    }

    // PATCH /api/lab/orders/{order}/status
    public function updateOrderStatus(Request $request, LabOrder $order): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,sample_collected,processing,completed,cancelled'],
        ]);

        $order->update(['status' => $request->status]);
        return $this->success(['status' => $order->status], 'Order status updated.');
    }
}
