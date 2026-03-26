<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'doctor_id'        => $this->doctor_id,
            'first_name'       => $this->first_name,
            'last_name'        => $this->last_name,
            'full_name'        => $this->full_name,
            'specialization'   => $this->specialization,
            'qualification'    => $this->qualification,
            'experience'       => $this->experience,
            'phone'            => $this->phone,
            'email'            => $this->email,
            'consultation_fee' => $this->consultation_fee,
            'gender'           => $this->gender,
            'status'           => $this->status,
            'photo_url'        => $this->photo_url,
            'department'       => $this->whenLoaded('department', fn() =>
                ['id' => $this->department->id, 'name' => $this->department->name]
            ),
        ];
    }
}
