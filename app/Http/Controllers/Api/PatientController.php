<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\AuditService;
use App\Services\FileUploadService;
use App\Services\PatientIdGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends BaseController
{
    public function __construct(
        private readonly AuditService      $audit,
        private readonly FileUploadService $fileUpload,
    ) {}

    // =========================================================================
    // GET /api/patients
    // Supports: ?search=, ?status=, ?gender=, ?blood_group=, ?per_page=
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $patients = Patient::search($request->search)
            ->when($request->status,      fn($q, $v) => $q->where('status', $v))
            ->when($request->gender,      fn($q, $v) => $q->where('gender', $v))
            ->when($request->blood_group, fn($q, $v) => $q->where('blood_group', $v))
            ->when($request->marital_status, fn($q, $v) => $q->where('marital_status', $v))
            ->orderBy('first_name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($patients, PatientResource::class);
    }

    // =========================================================================
    // POST /api/patients
    // =========================================================================
    public function store(StorePatientRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Auto-generate patient_id
        $data['patient_id']        = PatientIdGenerator::next();
        $data['registration_date'] = $data['registration_date'] ?? now()->toDateString();

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->fileUpload->upload($request->file('photo'), 'patients');
        }

        $patient = Patient::create($data);

        $this->audit->log(
            event:       'patient.created',
            description: "Patient registered: {$patient->patient_id} — {$patient->first_name} {$patient->last_name}",
            userId:      $request->user()->id,
            properties:  ['patient_id' => $patient->id],
        );

        return $this->created(new PatientResource($patient), 'Patient registered successfully.');
    }

    // =========================================================================
    // GET /api/patients/{patient}
    // Loads all relationships for full detail view
    // =========================================================================
    public function show(Patient $patient): JsonResponse
    {
        $patient->load(['appointments', 'opdVisits', 'admissions', 'prescriptions', 'labResults', 'invoices']);

        return $this->success(new PatientResource($patient), 'Patient retrieved.');
    }

    // =========================================================================
    // PUT /api/patients/{patient}
    // =========================================================================
    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->fileUpload->replace($request->file('photo'), $patient->photo, 'patients');
        }

        $patient->update($data);

        $this->audit->log(
            event:       'patient.updated',
            description: "Patient updated: {$patient->patient_id} — {$patient->first_name} {$patient->last_name}",
            userId:      $request->user()->id,
            properties:  ['patient_id' => $patient->id],
        );

        return $this->success(new PatientResource($patient), 'Patient updated successfully.');
    }

    // =========================================================================
    // DELETE /api/patients/{patient}
    // Soft delete — data retained for audit/medical history
    // =========================================================================
    public function destroy(Request $request, Patient $patient): JsonResponse
    {
        // Block delete if patient has active admissions
        if ($patient->admissions()->whereNull('discharge_date')->exists()) {
            return $this->error('Cannot delete a patient with an active admission.', 422);
        }

        $this->audit->log(
            event:       'patient.deleted',
            description: "Patient deleted: {$patient->patient_id} — {$patient->first_name} {$patient->last_name}",
            userId:      $request->user()->id,
            properties:  ['patient_id' => $patient->id],
        );

        $patient->delete();

        return $this->success(null, 'Patient deleted successfully.');
    }

    // =========================================================================
    // PATCH /api/patients/{patient}/toggle-status
    // =========================================================================
    public function toggleStatus(Request $request, Patient $patient): JsonResponse
    {
        $newStatus = $patient->status === 'active' ? 'inactive' : 'active';
        $patient->update(['status' => $newStatus]);

        return $this->success(['status' => $newStatus], "Patient status changed to {$newStatus}.");
    }
}
