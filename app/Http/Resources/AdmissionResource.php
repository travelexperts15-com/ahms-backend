<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'admission_number' => $this->admission_number,
            'admission_date'   => $this->admission_date?->toDateString(),
            'admission_time'   => $this->admission_time,
            'discharge_date'   => $this->discharge_date?->toDateString(),
            'discharge_time'   => $this->discharge_time,
            'admission_type'   => $this->admission_type,
            'status'           => $this->status,
            'length_of_stay'   => $this->length_of_stay,    // computed accessor (days)

            'reason_for_admission' => $this->reason_for_admission,
            'diagnosis'            => $this->diagnosis,
            'treatment_summary'    => $this->treatment_summary,
            'discharge_notes'      => $this->discharge_notes,
            'discharge_condition'  => $this->discharge_condition,

            'created_at' => $this->created_at?->toDateTimeString(),

            'patient' => $this->whenLoaded('patient', fn() => [
                'id'         => $this->patient->id,
                'patient_id' => $this->patient->patient_id,
                'first_name' => $this->patient->first_name,
                'last_name'  => $this->patient->last_name,
                'phone'      => $this->patient->phone,
                'gender'     => $this->patient->gender,
                'dob'        => $this->patient->dob?->toDateString(),
                'blood_group'=> $this->patient->blood_group,
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

            'bed' => $this->whenLoaded('bed', fn() => $this->bed ? [
                'id'         => $this->bed->id,
                'bed_number' => $this->bed->bed_number,
                'ward'       => $this->bed->ward,
                'type'       => $this->bed->type,
            ] : null),
        ];
    }
}
