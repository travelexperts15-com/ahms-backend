<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorScheduleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'day_of_week'       => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'start_time'        => ['required', 'date_format:H:i'],
            'end_time'          => ['required', 'date_format:H:i', 'after:start_time'],
            'max_patients'      => ['nullable', 'integer', 'min:1'],
            'consultation_room' => ['nullable', 'string', 'max:100'],
            'status'            => ['sometimes', 'in:active,inactive'],
        ];
    }
}
