<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Opd\StoreOpdVisitRequest;
use App\Http\Requests\Opd\UpdateOpdVisitRequest;
use App\Http\Resources\OpdVisitResource;
use App\Models\OpdVisit;
use App\Services\AuditService;
use App\Services\OpdVisitNumberGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpdController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // =========================================================================
    // GET /api/opd
    // Filters: ?search=, ?status=, ?doctor_id=, ?patient_id=,
    //          ?department_id=, ?date=, ?date_from=, ?date_to=, ?per_page=
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $visits = OpdVisit::with(['patient', 'doctor', 'department'])
            ->search($request->search)
            ->when($request->status,        fn($q, $v) => $q->where('status', $v))
            ->when($request->doctor_id,     fn($q, $v) => $q->where('doctor_id', $v))
            ->when($request->patient_id,    fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v))
            ->when($request->date,          fn($q, $v) => $q->whereDate('visit_date', $v))
            ->when($request->date_from,     fn($q, $v) => $q->whereDate('visit_date', '>=', $v))
            ->when($request->date_to,       fn($q, $v) => $q->whereDate('visit_date', '<=', $v))
            ->orderByDesc('visit_date')
            ->orderByDesc('visit_time')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($visits, OpdVisitResource::class);
    }

    // =========================================================================
    // POST /api/opd
    // =========================================================================
    public function store(StoreOpdVisitRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['visit_number'] = OpdVisitNumberGenerator::next();
        $data['created_by']   = $request->user()->id;

        $visit = OpdVisit::create($data);

        // Mark linked appointment as completed
        if ($visit->appointment_id) {
            $visit->appointment?->update(['status' => 'completed']);
        }

        $visit->load(['patient', 'doctor', 'department']);

        $this->audit->log(
            event:       'opd.visit_created',
            description: "OPD visit created: {$visit->visit_number} for patient #{$visit->patient->patient_id}",
            userId:      $request->user()->id,
            properties:  ['visit_id' => $visit->id],
        );

        return $this->created(new OpdVisitResource($visit), 'OPD visit recorded successfully.');
    }

    // =========================================================================
    // GET /api/opd/{visit}
    // =========================================================================
    public function show(OpdVisit $visit): JsonResponse
    {
        $visit->load(['patient', 'doctor', 'department', 'appointment', 'createdBy']);
        return $this->success(new OpdVisitResource($visit), 'OPD visit retrieved.');
    }

    // =========================================================================
    // PUT /api/opd/{visit}
    // =========================================================================
    public function update(UpdateOpdVisitRequest $request, OpdVisit $visit): JsonResponse
    {
        if ($visit->status === 'completed') {
            return $this->error('Cannot edit a completed OPD visit.', 422);
        }

        $visit->update($request->validated());
        $visit->load(['patient', 'doctor', 'department']);

        $this->audit->log(
            event:       'opd.visit_updated',
            description: "OPD visit updated: {$visit->visit_number}",
            userId:      $request->user()->id,
            properties:  ['visit_id' => $visit->id],
        );

        return $this->success(new OpdVisitResource($visit), 'OPD visit updated successfully.');
    }

    // =========================================================================
    // DELETE /api/opd/{visit}
    // =========================================================================
    public function destroy(Request $request, OpdVisit $visit): JsonResponse
    {
        if ($visit->status === 'completed') {
            return $this->error('Cannot delete a completed OPD visit.', 422);
        }

        $this->audit->log(
            event:       'opd.visit_deleted',
            description: "OPD visit deleted: {$visit->visit_number}",
            userId:      $request->user()->id,
            properties:  ['visit_id' => $visit->id],
        );

        $visit->delete();
        return $this->success(null, 'OPD visit deleted successfully.');
    }
}
