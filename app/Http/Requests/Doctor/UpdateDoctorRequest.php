<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('doctor')?->user_id;

        return [
            // User account fields (optional on update)
            'name'   => ['sometimes', 'string', 'max:191'],
            'email'  => ['sometimes', 'email', 'max:191', "unique:users,email,{$userId}"],
            'phone'  => ['nullable', 'string', 'max:30'],

            // Doctor profile fields
            'first_name'       => ['sometimes', 'string', 'max:100'],
            'last_name'        => ['sometimes', 'string', 'max:100'],
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
        ];
    }
}
