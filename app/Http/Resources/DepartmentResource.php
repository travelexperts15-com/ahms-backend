<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'code'                => $this->code,
            'description'         => $this->description,
            'head_of_department'  => $this->head_of_department,
            'phone'               => $this->phone,
            'email'               => $this->email,
            'location'            => $this->location,
            'status'              => $this->status,
            'doctors_count'       => $this->whenCounted('doctors'),
            'staff_count'         => $this->whenCounted('staffProfiles'),
            'created_at'          => $this->created_at?->toDateTimeString(),
        ];
    }
}
