<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'appointment_number' => $this->appointment_number,
            'appointment_date'   => $this->appointment_date?->toDateString(),
            'appointment_time'   => $this->appointment_time,
            'type'               => $this->type,
            'status'             => $this->status,
            'symptoms'           => $this->symptoms,
            'notes'              => $this->notes,
            'created_at'         => $this->created_at?->toDateTimeString(),

            // ── Relations (only when eager-loaded) ────────────────────────────
            'patient' => $this->whenLoaded('patient', fn() => [
                'id'         => $this->patient->id,
                'patient_id' => $this->patient->patient_id,
                'first_name' => $this->patient->first_name,
                'last_name'  => $this->patient->last_name,
                'phone'      => $this->patient->phone,
                'gender'     => $this->patient->gender,
            ]),

            'doctor' => $this->whenLoaded('doctor', fn() => [
                'id'             => $this->doctor->id,
                'doctor_id'      => $this->doctor->doctor_id,
                'first_name'     => $this->doctor->first_name,
                'last_name'      => $this->doctor->last_name,
                'specialization' => $this->doctor->specialization,
            ]),

            'department' => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),

            'booked_by' => $this->whenLoaded('bookedBy', fn() => [
                'id'   => $this->bookedBy->id,
                'name' => $this->bookedBy->name,
            ]),
        ];
    }
}
