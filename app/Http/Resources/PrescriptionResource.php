<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'prescription_number' => $this->prescription_number,
            'prescribed_date'     => $this->prescribed_date?->toDateString(),
            'status'              => $this->status,
            'notes'               => $this->notes,
            'created_at'          => $this->created_at?->toDateTimeString(),

            'items' => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'            => $item->id,
                    'medicine_name' => $item->medicine_name,
                    'dosage'        => $item->dosage,
                    'frequency'     => $item->frequency,
                    'duration'      => $item->duration,
                    'route'         => $item->route,
                    'instructions'  => $item->instructions,
                    'quantity'      => $item->quantity,
                ])
            ),

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
        ];
    }
}
