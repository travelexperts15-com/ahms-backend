<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'bed_number'     => $this->bed_number,
            'ward'           => $this->ward,
            'room_number'    => $this->room_number,
            'type'           => $this->type,
            'status'         => $this->status,
            'charge_per_day' => $this->charge_per_day,
            'notes'          => $this->notes,
            'department'     => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
            'current_patient' => $this->whenLoaded('currentAdmission', fn() =>
                $this->currentAdmission ? [
                    'admission_number' => $this->currentAdmission->admission_number,
                    'patient_id'       => $this->currentAdmission->patient->patient_id ?? null,
                    'patient_name'     => $this->currentAdmission->patient
                        ? $this->currentAdmission->patient->first_name . ' ' . $this->currentAdmission->patient->last_name
                        : null,
                    'admission_date'   => $this->currentAdmission->admission_date?->toDateString(),
                ] : null
            ),
        ];
    }
}
