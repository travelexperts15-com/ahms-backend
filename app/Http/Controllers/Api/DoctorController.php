<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\StoreDoctorScheduleRequest;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\DoctorScheduleResource;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Services\AuditService;
use App\Services\FileUploadService;
use App\Services\UserIdGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorController extends BaseController
{
    public function __construct(
        private readonly AuditService     $audit,
        private readonly FileUploadService $fileUpload,
    ) {}

    // =========================================================================
    // GET /api/doctors
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $doctors = Doctor::with('department')
            ->search($request->search)
            ->when($request->department_id, fn($q, $d) => $q->where('department_id', $d))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderBy('first_name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($doctors, DoctorResource::class);
    }

    // =========================================================================
    // POST /api/doctors
    // Creates user account + doctor profile in one transaction
    // =========================================================================
    public function store(StoreDoctorRequest $request): JsonResponse
    {
        $doctor = DB::transaction(function () use ($request) {
            $user = User::create([
                'name'        => $request->name,
                'email'       => $request->email,
                'password'    => Hash::make($request->password),
                'phone'       => $request->phone,
                'gender'      => $request->gender ?? null,
                'employee_id' => UserIdGenerator::next(),
                'is_active'   => true,
            ]);

            $user->assignRole('doctor');

            $profileData = $request->only([
                'first_name', 'last_name', 'department_id', 'specialization',
                'qualification', 'experience', 'consultation_fee',
                'address', 'gender', 'bio', 'status',
            ]);

            // Generate unique doctor_id: DOC-0001 format
            $last = Doctor::max('id') ?? 0;
            $profileData['doctor_id']  = 'DOC-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
            $profileData['user_id']    = $user->id;
            $profileData['email']      = $request->email;
            $profileData['phone']      = $request->phone;

            if ($request->hasFile('photo')) {
                $profileData['photo'] = $this->fileUpload->upload($request->file('photo'), 'doctors');
            }

            $doctor = Doctor::create($profileData);

            // Optional schedules
            if ($request->filled('schedules')) {
                foreach ($request->schedules as $schedule) {
                    $doctor->schedules()->create($schedule);
                }
            }

            return $doctor;
        });

        $doctor->load('department');

        $this->audit->log(
            event:       'doctor.created',
            description: "Doctor created: {$doctor->full_name}",
            userId:      $request->user()->id,
            properties:  ['doctor_id' => $doctor->id],
        );

        return $this->created(new DoctorResource($doctor), 'Doctor created successfully.');
    }

    // =========================================================================
    // GET /api/doctors/{doctor}
    // =========================================================================
    public function show(Doctor $doctor): JsonResponse
    {
        $doctor->load(['department', 'schedules']);
        return $this->success(new DoctorResource($doctor), 'Doctor retrieved.');
    }

    // =========================================================================
    // PUT /api/doctors/{doctor}
    // =========================================================================
    public function update(UpdateDoctorRequest $request, Doctor $doctor): JsonResponse
    {
        $data = $request->only([
            'first_name', 'last_name', 'department_id', 'specialization',
            'qualification', 'experience', 'consultation_fee',
            'address', 'gender', 'bio', 'status',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->fileUpload->replace($request->file('photo'), $doctor->photo, 'doctors');
        }

        // Sync user account fields if provided
        $userFields = array_filter($request->only(['name', 'email', 'phone']), fn($v) => $v !== null);
        if (!empty($userFields)) {
            $doctor->user->update($userFields);
            if (isset($userFields['email'])) $data['email'] = $userFields['email'];
            if (isset($userFields['phone']))  $data['phone'] = $userFields['phone'];
        }

        $doctor->update($data);

        $this->audit->log(
            event:       'doctor.updated',
            description: "Doctor updated: {$doctor->full_name}",
            userId:      $request->user()->id,
            properties:  ['doctor_id' => $doctor->id],
        );

        $doctor->load('department');
        return $this->success(new DoctorResource($doctor), 'Doctor updated successfully.');
    }

    // =========================================================================
    // DELETE /api/doctors/{doctor}
    // =========================================================================
    public function destroy(Request $request, Doctor $doctor): JsonResponse
    {
        $name = $doctor->full_name;

        DB::transaction(function () use ($doctor) {
            if ($doctor->photo) $this->fileUpload->delete($doctor->photo);
            $doctor->schedules()->delete();
            $doctor->user->tokens()->delete();
            $doctor->delete();
            $doctor->user->delete();
        });

        $this->audit->log(
            event:       'doctor.deleted',
            description: "Doctor deleted: {$name}",
            userId:      $request->user()->id,
            properties:  ['doctor_id' => $doctor->id],
        );

        return $this->success(null, 'Doctor removed successfully.');
    }

    // =========================================================================
    // PATCH /api/doctors/{doctor}/toggle-status
    // =========================================================================
    public function toggleStatus(Request $request, Doctor $doctor): JsonResponse
    {
        $newStatus = $doctor->status === 'active' ? 'inactive' : 'active';
        $doctor->update(['status' => $newStatus]);
        $doctor->user->update(['is_active' => $newStatus === 'active']);

        return $this->success(['status' => $newStatus], "Doctor status changed to {$newStatus}.");
    }

    // =========================================================================
    // Schedule sub-resource
    // =========================================================================

    // GET /api/doctors/{doctor}/schedules
    public function schedules(Doctor $doctor): JsonResponse
    {
        $doctor->load('schedules');
        return $this->success(DoctorScheduleResource::collection($doctor->schedules), 'Schedules retrieved.');
    }

    // POST /api/doctors/{doctor}/schedules
    public function storeSchedule(StoreDoctorScheduleRequest $request, Doctor $doctor): JsonResponse
    {
        $schedule = $doctor->schedules()->updateOrCreate(
            ['day_of_week' => $request->day_of_week],
            $request->validated(),
        );

        return $this->created(new DoctorScheduleResource($schedule), 'Schedule saved.');
    }

    // DELETE /api/doctors/{doctor}/schedules/{schedule}
    public function destroySchedule(Doctor $doctor, DoctorSchedule $schedule): JsonResponse
    {
        if ($schedule->doctor_id !== $doctor->id) {
            return $this->error('Schedule does not belong to this doctor.', 422);
        }

        $schedule->delete();
        return $this->success(null, 'Schedule removed.');
    }
}
