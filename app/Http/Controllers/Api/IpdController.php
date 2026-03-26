<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Ipd\DischargePatientRequest;
use App\Http\Requests\Ipd\StoreAdmissionRequest;
use App\Http\Resources\AdmissionResource;
use App\Models\Admission;
use App\Models\Bed;
use App\Services\AdmissionNumberGenerator;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IpdController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // =========================================================================
    // GET /api/ipd
    // Filters: ?search=, ?status=, ?doctor_id=, ?patient_id=,
    //          ?department_id=, ?date_from=, ?date_to=, ?per_page=
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $admissions = Admission::with(['patient', 'doctor', 'department', 'bed'])
            ->search($request->search)
            ->when($request->status,        fn($q, $v) => $q->where('status', $v))
            ->when($request->doctor_id,     fn($q, $v) => $q->where('doctor_id', $v))
            ->when($request->patient_id,    fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v))
            ->when($request->date_from,     fn($q, $v) => $q->whereDate('admission_date', '>=', $v))
            ->when($request->date_to,       fn($q, $v) => $q->whereDate('admission_date', '<=', $v))
            ->orderByDesc('admission_date')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($admissions, AdmissionResource::class);
    }

    // =========================================================================
    // POST /api/ipd — Admit patient
    // =========================================================================
    public function store(StoreAdmissionRequest $request): JsonResponse
    {
        // Check if patient already has an active admission
        $existing = Admission::where('patient_id', $request->patient_id)
            ->where('status', 'admitted')
            ->first();

        if ($existing) {
            return $this->error("Patient is already admitted (#{$existing->admission_number}).", 422);
        }

        // Check bed is available if provided
        if ($request->bed_id) {
            $bed = Bed::find($request->bed_id);
            if ($bed && $bed->status !== 'available') {
                return $this->error("Bed {$bed->bed_number} is not available.", 422);
            }
        }

        $admission = DB::transaction(function () use ($request) {
            $data                     = $request->validated();
            $data['admission_number'] = AdmissionNumberGenerator::next();
            $data['admitted_by']      = $request->user()->id;

            $admission = Admission::create($data);

            // Mark bed as occupied
            if ($admission->bed_id) {
                Bed::where('id', $admission->bed_id)->update(['status' => 'occupied']);
            }

            return $admission;
        });

        $admission->load(['patient', 'doctor', 'department', 'bed']);

        $this->audit->log(
            event:       'ipd.admitted',
            description: "Patient admitted: {$admission->admission_number} — {$admission->patient->first_name} {$admission->patient->last_name}",
            userId:      $request->user()->id,
            properties:  ['admission_id' => $admission->id],
        );

        return $this->created(new AdmissionResource($admission), 'Patient admitted successfully.');
    }

    // =========================================================================
    // GET /api/ipd/{admission}
    // =========================================================================
    public function show(Admission $admission): JsonResponse
    {
        $admission->load(['patient', 'doctor', 'department', 'bed', 'admittedBy', 'dischargedBy']);
        return $this->success(new AdmissionResource($admission), 'Admission retrieved.');
    }

    // =========================================================================
    // PUT /api/ipd/{admission} — Update admission details (not discharge)
    // =========================================================================
    public function update(Request $request, Admission $admission): JsonResponse
    {
        if ($admission->status !== 'admitted') {
            return $this->error("Cannot update a {$admission->status} admission.", 422);
        }

        $request->validate([
            'doctor_id'            => ['sometimes', 'exists:doctors,id'],
            'department_id'        => ['nullable', 'exists:departments,id'],
            'bed_id'               => ['nullable', 'exists:beds,id'],
            'reason_for_admission' => ['nullable', 'string'],
            'diagnosis'            => ['nullable', 'string'],
            'admission_type'       => ['sometimes', 'in:regular,emergency,transfer'],
        ]);

        // Handle bed change
        if ($request->has('bed_id') && $request->bed_id !== $admission->bed_id) {
            DB::transaction(function () use ($request, $admission) {
                // Free old bed
                if ($admission->bed_id) {
                    Bed::where('id', $admission->bed_id)->update(['status' => 'available']);
                }
                // Occupy new bed
                if ($request->bed_id) {
                    Bed::where('id', $request->bed_id)->update(['status' => 'occupied']);
                }
                $admission->update($request->only(['doctor_id', 'department_id', 'bed_id', 'reason_for_admission', 'diagnosis', 'admission_type']));
            });
        } else {
            $admission->update($request->only(['doctor_id', 'department_id', 'bed_id', 'reason_for_admission', 'diagnosis', 'admission_type']));
        }

        $admission->load(['patient', 'doctor', 'department', 'bed']);
        return $this->success(new AdmissionResource($admission), 'Admission updated successfully.');
    }

    // =========================================================================
    // POST /api/ipd/{admission}/discharge — Discharge patient
    // =========================================================================
    public function discharge(DischargePatientRequest $request, Admission $admission): JsonResponse
    {
        if ($admission->status !== 'admitted') {
            return $this->error("Patient is already {$admission->status}.", 422);
        }

        DB::transaction(function () use ($request, $admission) {
            $admission->update([
                'discharge_date'      => $request->discharge_date,
                'discharge_time'      => $request->discharge_time,
                'diagnosis'           => $request->diagnosis ?? $admission->diagnosis,
                'treatment_summary'   => $request->treatment_summary,
                'discharge_notes'     => $request->discharge_notes,
                'discharge_condition' => $request->discharge_condition,
                'status'              => 'discharged',
                'discharged_by'       => $request->user()->id,
            ]);

            // Free the bed
            if ($admission->bed_id) {
                Bed::where('id', $admission->bed_id)->update(['status' => 'available']);
            }
        });

        $admission->load(['patient', 'doctor', 'department', 'bed']);

        $this->audit->log(
            event:       'ipd.discharged',
            description: "Patient discharged: {$admission->admission_number} — {$admission->patient->first_name} {$admission->patient->last_name}",
            userId:      $request->user()->id,
            properties:  ['admission_id' => $admission->id, 'condition' => $request->discharge_condition],
        );

        return $this->success(new AdmissionResource($admission), 'Patient discharged successfully.');
    }
}
