<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'day_of_week'       => $this->day_of_week,
            'start_time'        => $this->start_time,
            'end_time'          => $this->end_time,
            'max_patients'      => $this->max_patients,
            'consultation_room' => $this->consultation_room,
            'status'            => $this->status,
        ];
    }
}
