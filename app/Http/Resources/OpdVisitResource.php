<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpdVisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'visit_number' => $this->visit_number,
            'visit_date'   => $this->visit_date?->toDateString(),
            'visit_time'   => $this->visit_time,
            'status'       => $this->status,

            // ── Clinical ──────────────────────────────────────────────────────
            'chief_complaint'      => $this->chief_complaint,
            'history_of_illness'   => $this->history_of_illness,
            'examination_findings' => $this->examination_findings,
            'diagnosis'            => $this->diagnosis,
            'treatment_plan'       => $this->treatment_plan,
            'notes'                => $this->notes,

            // ── Vitals ────────────────────────────────────────────────────────
            'vitals' => [
                'blood_pressure'    => $this->blood_pressure,
                'temperature'       => $this->temperature,
                'pulse_rate'        => $this->pulse_rate,
                'respiratory_rate'  => $this->respiratory_rate,
                'weight'            => $this->weight,
                'height'            => $this->height,
                'oxygen_saturation' => $this->oxygen_saturation,
            ],

            'created_at' => $this->created_at?->toDateTimeString(),

            // ── Relations ─────────────────────────────────────────────────────
            'patient' => $this->whenLoaded('patient', fn() => [
                'id'         => $this->patient->id,
                'patient_id' => $this->patient->patient_id,
                'first_name' => $this->patient->first_name,
                'last_name'  => $this->patient->last_name,
                'phone'      => $this->patient->phone,
                'gender'     => $this->patient->gender,
                'dob'        => $this->patient->dob?->toDateString(),
            ]),

            'doctor' => $this->whenLoaded('doctor', fn() => [
                'id'             => $this->doctor->id,
                'first_name'     => $this->doctor->first_name,
                'last_name'      => $this->doctor->last_name,
                'specialization' => $this->doctor->specialization,
            ]),

            'department' => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
        ];
    }
}
