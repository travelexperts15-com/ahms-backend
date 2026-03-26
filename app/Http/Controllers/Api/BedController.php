<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Bed\StoreBedRequest;
use App\Http\Requests\Bed\UpdateBedRequest;
use App\Http\Resources\BedResource;
use App\Models\Bed;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BedController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // GET /api/beds — Filters: ?status=, ?type=, ?department_id=, ?ward=, ?search=
    public function index(Request $request): JsonResponse
    {
        $beds = Bed::with(['department'])
            ->search($request->search)
            ->when($request->status,        fn($q, $v) => $q->where('status', $v))
            ->when($request->type,          fn($q, $v) => $q->where('type', $v))
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v))
            ->when($request->ward,          fn($q, $v) => $q->where('ward', 'like', "%{$v}%"))
            ->orderBy('bed_number')
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($beds, BedResource::class);
    }

    // GET /api/beds/available — Quick list for admission form dropdowns
    public function available(Request $request): JsonResponse
    {
        $beds = Bed::with('department')
            ->available()
            ->when($request->type,          fn($q, $v) => $q->where('type', $v))
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v))
            ->orderBy('bed_number')
            ->get();

        return $this->success(BedResource::collection($beds), 'Available beds retrieved.');
    }

    // POST /api/beds
    public function store(StoreBedRequest $request): JsonResponse
    {
        $bed = Bed::create($request->validated());

        $this->audit->log(
            event:       'bed.created',
            description: "Bed created: {$bed->bed_number}",
            userId:      $request->user()->id,
        );

        return $this->created(new BedResource($bed->load('department')), 'Bed created successfully.');
    }

    // GET /api/beds/{bed}
    public function show(Bed $bed): JsonResponse
    {
        $bed->load(['department', 'currentAdmission.patient']);
        return $this->success(new BedResource($bed), 'Bed retrieved.');
    }

    // PUT /api/beds/{bed}
    public function update(UpdateBedRequest $request, Bed $bed): JsonResponse
    {
        if ($bed->status === 'occupied' && $request->status === 'available') {
            return $this->error('Cannot set an occupied bed to available. Discharge the patient first.', 422);
        }

        $bed->update($request->validated());

        $this->audit->log(
            event:       'bed.updated',
            description: "Bed updated: {$bed->bed_number}",
            userId:      $request->user()->id,
        );

        return $this->success(new BedResource($bed->load('department')), 'Bed updated successfully.');
    }

    // DELETE /api/beds/{bed}
    public function destroy(Request $request, Bed $bed): JsonResponse
    {
        if ($bed->status === 'occupied') {
            return $this->error('Cannot delete an occupied bed.', 422);
        }

        $this->audit->log(
            event:       'bed.deleted',
            description: "Bed deleted: {$bed->bed_number}",
            userId:      $request->user()->id,
        );

        $bed->delete();
        return $this->success(null, 'Bed deleted successfully.');
    }
}
