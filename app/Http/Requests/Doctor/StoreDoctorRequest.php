<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // User account fields
            'name'              => ['required', 'string', 'max:191'],
            'email'             => ['required', 'email', 'max:191', 'unique:users,email'],
            'password'          => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'phone'             => ['nullable', 'string', 'max:30'],

            // Doctor profile fields
            'first_name'       => ['required', 'string', 'max:100'],
            'last_name'        => ['required', 'string', 'max:100'],
            'department_id'    => ['nullable', 'exists:departments,id'],
            'specialization'   => ['nullable', 'string', 'max:191'],
            'qualification'    => ['nullable', 'string', 'max:191'],
            'experience'       => ['nullable', 'integer', 'min:0', 'max:60'],
            'consultation_fee' => ['nullable', 'numeric', 'min:0'],
            'address'          => ['nullable', 'string'],
            'gender'           => ['nullable', 'in:male,female,other'],
            'photo'            => ['nullable', 'image', 'max:2048'],
            'bio'              => ['nullable', 'string'],
            'status'           => ['sometimes', 'in:active,inactive,on_leave'],

            // Schedule (optional on create)
            'schedules'                       => ['nullable', 'array'],
            'schedules.*.day_of_week'         => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'schedules.*.start_time'          => ['required', 'date_format:H:i'],
            'schedules.*.end_time'            => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.max_patients'        => ['nullable', 'integer', 'min:1'],
            'schedules.*.consultation_room'   => ['nullable', 'string', 'max:100'],
        ];
    }
}
