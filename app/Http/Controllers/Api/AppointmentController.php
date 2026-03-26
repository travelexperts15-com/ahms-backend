<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentNumberGenerator;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // =========================================================================
    // GET /api/appointments
    // Filters: ?search=, ?status=, ?type=, ?doctor_id=, ?patient_id=,
    //          ?department_id=, ?date=, ?date_from=, ?date_to=, ?per_page=
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $appointments = Appointment::with(['patient', 'doctor', 'department'])
            ->search($request->search)
            ->when($request->status,        fn($q, $v) => $q->where('status', $v))
            ->when($request->type,          fn($q, $v) => $q->where('type', $v))
            ->when($request->doctor_id,     fn($q, $v) => $q->where('doctor_id', $v))
            ->when($request->patient_id,    fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->department_id, fn($q, $v) => $q->where('department_id', $v))
            ->when($request->date,          fn($q, $v) => $q->whereDate('appointment_date', $v))
            ->when($request->date_from,     fn($q, $v) => $q->whereDate('appointment_date', '>=', $v))
            ->when($request->date_to,       fn($q, $v) => $q->whereDate('appointment_date', '<=', $v))
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($appointments, AppointmentResource::class);
    }

    // =========================================================================
    // POST /api/appointments
    // =========================================================================
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['appointment_number'] = AppointmentNumberGenerator::next();
        $data['booked_by']          = $request->user()->id;

        $appointment = Appointment::create($data);
        $appointment->load(['patient', 'doctor', 'department', 'bookedBy']);

        $this->audit->log(
            event:       'appointment.created',
            description: "Appointment booked: {$appointment->appointment_number} for patient #{$appointment->patient->patient_id}",
            userId:      $request->user()->id,
            properties:  ['appointment_id' => $appointment->id],
        );

        return $this->created(new AppointmentResource($appointment), 'Appointment booked successfully.');
    }

    // =========================================================================
    // GET /api/appointments/{appointment}
    // =========================================================================
    public function show(Appointment $appointment): JsonResponse
    {
        $appointment->load(['patient', 'doctor', 'department', 'bookedBy']);
        return $this->success(new AppointmentResource($appointment), 'Appointment retrieved.');
    }

    // =========================================================================
    // PUT /api/appointments/{appointment}
    // =========================================================================
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        // Block editing of already completed/cancelled appointments
        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            return $this->error("Cannot edit a {$appointment->status} appointment.", 422);
        }

        $appointment->update($request->validated());
        $appointment->load(['patient', 'doctor', 'department', 'bookedBy']);

        $this->audit->log(
            event:       'appointment.updated',
            description: "Appointment updated: {$appointment->appointment_number}",
            userId:      $request->user()->id,
            properties:  ['appointment_id' => $appointment->id],
        );

        return $this->success(new AppointmentResource($appointment), 'Appointment updated successfully.');
    }

    // =========================================================================
    // DELETE /api/appointments/{appointment}
    // =========================================================================
    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->status === 'completed') {
            return $this->error('Cannot delete a completed appointment.', 422);
        }

        $this->audit->log(
            event:       'appointment.deleted',
            description: "Appointment deleted: {$appointment->appointment_number}",
            userId:      $request->user()->id,
            properties:  ['appointment_id' => $appointment->id],
        );

        $appointment->delete();
        return $this->success(null, 'Appointment deleted successfully.');
    }

    // =========================================================================
    // PATCH /api/appointments/{appointment}/status
    // Body: { "status": "confirmed|completed|cancelled|no_show" }
    // =========================================================================
    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:scheduled,confirmed,completed,cancelled,no_show'],
        ]);

        $appointment->update(['status' => $request->status]);

        $this->audit->log(
            event:       'appointment.status_changed',
            description: "Appointment {$appointment->appointment_number} → {$request->status}",
            userId:      $request->user()->id,
        );

        return $this->success(['status' => $appointment->status], 'Appointment status updated.');
    }

    // =========================================================================
    // GET /api/appointments/today
    // Quick view for today's appointments (used in dashboard)
    // =========================================================================
    public function today(Request $request): JsonResponse
    {
        $appointments = Appointment::with(['patient', 'doctor'])
            ->whereDate('appointment_date', today())
            ->when($request->doctor_id, fn($q, $v) => $q->where('doctor_id', $v))
            ->when($request->status,    fn($q, $v) => $q->where('status', $v))
            ->orderBy('appointment_time')
            ->get();

        return $this->success(AppointmentResource::collection($appointments), "Today's appointments retrieved.");
    }
}
