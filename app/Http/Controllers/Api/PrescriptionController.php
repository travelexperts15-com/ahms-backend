<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Prescription\StorePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // GET /api/prescriptions — ?search=, ?status=, ?patient_id=, ?doctor_id=, ?date_from=, ?date_to=
    public function index(Request $request): JsonResponse
    {
        $prescriptions = Prescription::with(['patient', 'doctor'])
            ->search($request->search)
            ->when($request->status,     fn($q, $v) => $q->where('status', $v))
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->doctor_id,  fn($q, $v) => $q->where('doctor_id', $v))
            ->when($request->date_from,  fn($q, $v) => $q->whereDate('prescribed_date', '>=', $v))
            ->when($request->date_to,    fn($q, $v) => $q->whereDate('prescribed_date', '<=', $v))
            ->orderByDesc('prescribed_date')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($prescriptions, PrescriptionResource::class);
    }

    // POST /api/prescriptions — creates prescription + items in one transaction
    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        $prescription = DB::transaction(function () use ($request) {
            $last   = Prescription::whereNotNull('prescription_number')->max('prescription_number');
            $number = $last ? 'RX-' . str_pad((int) preg_replace('/\D/', '', $last) + 1, 4, '0', STR_PAD_LEFT) : 'RX-0001';

            $prescription = Prescription::create([
                'prescription_number' => $number,
                'patient_id'          => $request->patient_id,
                'doctor_id'           => $request->doctor_id,
                'opd_visit_id'        => $request->opd_visit_id,
                'admission_id'        => $request->admission_id,
                'prescribed_date'     => $request->prescribed_date,
                'notes'               => $request->notes,
                'created_by'          => $request->user()->id,
            ]);

            foreach ($request->items as $item) {
                $prescription->items()->create($item);
            }

            return $prescription;
        });

        $prescription->load(['patient', 'doctor', 'items']);

        $this->audit->log(
            event:       'prescription.created',
            description: "Prescription created: {$prescription->prescription_number}",
            userId:      $request->user()->id,
            properties:  ['prescription_id' => $prescription->id],
        );

        return $this->created(new PrescriptionResource($prescription), 'Prescription created successfully.');
    }

    // GET /api/prescriptions/{prescription}
    public function show(Prescription $prescription): JsonResponse
    {
        $prescription->load(['patient', 'doctor', 'items', 'opdVisit', 'admission']);
        return $this->success(new PrescriptionResource($prescription), 'Prescription retrieved.');
    }

    // PATCH /api/prescriptions/{prescription}/status
    public function updateStatus(Request $request, Prescription $prescription): JsonResponse
    {
        $request->validate(['status' => ['required', 'in:pending,dispensed,cancelled']]);

        $prescription->update(['status' => $request->status]);

        $this->audit->log(
            event:       'prescription.status_changed',
            description: "Prescription {$prescription->prescription_number} → {$request->status}",
            userId:      $request->user()->id,
        );

        return $this->success(['status' => $prescription->status], 'Status updated.');
    }
}
