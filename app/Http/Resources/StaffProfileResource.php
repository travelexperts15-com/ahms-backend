<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'first_name'              => $this->first_name,
            'last_name'               => $this->last_name,
            'full_name'               => $this->full_name,
            'dob'                     => $this->dob?->toDateString(),
            'position'                => $this->position,
            'joining_date'            => $this->joining_date?->toDateString(),
            'employment_type'         => $this->employment_type,
            'marital_status'          => $this->marital_status,
            'emergency_contact_name'  => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'department'              => $this->whenLoaded('department', fn() =>
                ['id' => $this->department->id, 'name' => $this->department->name]
            ),
        ];
    }
}
