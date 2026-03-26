<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'order_number'   => $this->order_number,
            'ordered_date'   => $this->ordered_date?->toDateString(),
            'clinical_notes' => $this->clinical_notes,
            'status'         => $this->status,
            'created_at'     => $this->created_at?->toDateTimeString(),

            'patient' => $this->whenLoaded('patient', fn() => [
                'id'         => $this->patient->id,
                'patient_id' => $this->patient->patient_id,
                'first_name' => $this->patient->first_name,
                'last_name'  => $this->patient->last_name,
            ]),

            'doctor' => $this->whenLoaded('doctor', fn() => [
                'id'         => $this->doctor->id,
                'first_name' => $this->doctor->first_name,
                'last_name'  => $this->doctor->last_name,
            ]),

            'results' => $this->whenLoaded('results', fn() =>
                $this->results->map(fn($r) => [
                    'id'           => $r->id,
                    'test_name'    => $r->labTest?->name,
                    'test_code'    => $r->labTest?->code,
                    'result_value' => $r->result_value,
                    'unit'         => $r->unit,
                    'normal_range' => $r->normal_range,
                    'result_flag'  => $r->result_flag,
                    'remarks'      => $r->remarks,
                    'resulted_at'  => $r->resulted_at?->toDateTimeString(),
                ])
            ),
        ];
    }
}
