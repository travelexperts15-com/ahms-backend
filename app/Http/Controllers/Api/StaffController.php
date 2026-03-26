<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Http\Resources\UserResource;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserIdGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // =========================================================================
    // GET /api/staff
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $staff = User::with(['roles', 'staffProfile.department'])
            ->whereHas('staffProfile')
            ->search($request->search)
            ->when($request->department_id, fn($q, $d) =>
                $q->whereHas('staffProfile', fn($q) => $q->where('department_id', $d))
            )
            ->when($request->role, fn($q, $r) => $q->role($r))
            ->when(isset($request->is_active), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($staff, UserResource::class);
    }

    // =========================================================================
    // POST /api/staff
    // Creates user account + staff profile in one transaction
    // =========================================================================
    public function store(StoreStaffRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name'        => $request->name,
                'email'       => $request->email,
                'password'    => Hash::make($request->password),
                'phone'       => $request->phone,
                'gender'      => $request->gender,
                'employee_id' => UserIdGenerator::next(),
                'is_active'   => true,
            ]);

            $user->assignRole($request->role);

            $user->staffProfile()->create($request->only([
                'first_name', 'last_name', 'department_id', 'dob', 'address',
                'emergency_contact_name', 'emergency_contact_phone',
                'joining_date', 'position', 'basic_salary',
                'bank_account', 'national_id', 'marital_status', 'employment_type',
            ]));

            return $user;
        });

        $user->load(['roles', 'staffProfile.department']);

        $this->audit->log(
            event:       'staff.created',
            description: "Staff created: {$user->email} | Role: {$request->role}",
            userId:      $request->user()->id,
            properties:  ['new_user_id' => $user->id],
        );

        return $this->created(new UserResource($user), 'Staff member created successfully.');
    }

    // =========================================================================
    // GET /api/staff/{staff}  — route model binds StaffProfile
    // =========================================================================
    public function show(StaffProfile $staff): JsonResponse
    {
        $staff->load(['user.roles', 'department']);
        return $this->success(new UserResource($staff->user), 'Staff member retrieved.');
    }

    // =========================================================================
    // PUT /api/staff/{staff}
    // =========================================================================
    public function update(UpdateStaffRequest $request, StaffProfile $staff): JsonResponse
    {
        DB::transaction(function () use ($request, $staff) {
            // Update user account fields if provided
            $userFields = $request->only(['name', 'email', 'phone', 'gender']);
            if (!empty(array_filter($userFields, fn($v) => $v !== null))) {
                $staff->user->update(array_filter($userFields, fn($v) => $v !== null));
            }

            // Update staff profile fields
            $profileFields = $request->only([
                'first_name', 'last_name', 'department_id', 'dob', 'address',
                'emergency_contact_name', 'emergency_contact_phone',
                'joining_date', 'position', 'basic_salary',
                'bank_account', 'national_id', 'marital_status', 'employment_type',
            ]);
            $staff->update($profileFields);
        });

        $staff->load(['user.roles', 'department']);

        $this->audit->log(
            event:       'staff.updated',
            description: "Staff updated: {$staff->user->email}",
            userId:      $request->user()->id,
            properties:  ['staff_id' => $staff->id],
        );

        return $this->success(new UserResource($staff->user), 'Staff member updated successfully.');
    }

    // =========================================================================
    // DELETE /api/staff/{staff}  — soft deletes user, hard deletes profile
    // =========================================================================
    public function destroy(Request $request, StaffProfile $staff): JsonResponse
    {
        $email = $staff->user->email;

        DB::transaction(function () use ($staff) {
            $staff->user->tokens()->delete();
            $staff->delete();
            $staff->user->delete();  // soft delete via SoftDeletes
        });

        $this->audit->log(
            event:       'staff.deleted',
            description: "Staff deleted: {$email}",
            userId:      $request->user()->id,
            properties:  ['staff_id' => $staff->id],
        );

        return $this->success(null, 'Staff member removed successfully.');
    }

    // =========================================================================
    // PATCH /api/staff/{staff}/toggle-status
    // =========================================================================
    public function toggleStatus(Request $request, StaffProfile $staff): JsonResponse
    {
        $staff->user->update(['is_active' => !$staff->user->is_active]);

        $status = $staff->user->is_active ? 'activated' : 'deactivated';

        $this->audit->log(
            event:       "staff.{$status}",
            description: "Staff {$status}: {$staff->user->email}",
            userId:      $request->user()->id,
        );

        return $this->success(
            ['is_active' => $staff->user->is_active],
            "Staff member {$status} successfully."
        );
    }
}
