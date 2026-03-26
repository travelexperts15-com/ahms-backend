<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // =========================================================================
    // GET /api/departments
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $departments = Department::withCount(['doctors', 'staffProfiles'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, fn($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%");
            }))
            ->orderBy('name')
            ->get();

        return $this->success(DepartmentResource::collection($departments), 'Departments retrieved.');
    }

    // =========================================================================
    // POST /api/departments
    // =========================================================================
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());

        $this->audit->log(
            event:       'department.created',
            description: "Department created: {$department->name}",
            userId:      $request->user()->id,
            properties:  ['department_id' => $department->id],
        );

        return $this->created(new DepartmentResource($department), 'Department created successfully.');
    }

    // =========================================================================
    // GET /api/departments/{department}
    // =========================================================================
    public function show(Department $department): JsonResponse
    {
        $department->loadCount(['doctors', 'staffProfiles']);
        return $this->success(new DepartmentResource($department), 'Department retrieved.');
    }

    // =========================================================================
    // PUT /api/departments/{department}
    // =========================================================================
    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());

        $this->audit->log(
            event:       'department.updated',
            description: "Department updated: {$department->name}",
            userId:      $request->user()->id,
            properties:  ['department_id' => $department->id],
        );

        return $this->success(new DepartmentResource($department), 'Department updated successfully.');
    }

    // =========================================================================
    // DELETE /api/departments/{department}
    // =========================================================================
    public function destroy(Request $request, Department $department): JsonResponse
    {
        // Block delete if department has active doctors or staff
        if ($department->doctors()->exists() || $department->staffProfiles()->exists()) {
            return $this->error('Cannot delete a department that has doctors or staff assigned.', 422);
        }

        $this->audit->log(
            event:       'department.deleted',
            description: "Department deleted: {$department->name}",
            userId:      $request->user()->id,
            properties:  ['department_id' => $department->id],
        );

        $department->delete();

        return $this->success(null, 'Department deleted successfully.');
    }
}
