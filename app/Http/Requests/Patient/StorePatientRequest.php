<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'               => ['required', 'string', 'max:100'],
            'last_name'                => ['required', 'string', 'max:100'],
            'gender'                   => ['required', 'in:male,female,other'],
            'dob'                      => ['required', 'date', 'before:today'],
            'blood_group'              => ['nullable', 'string', 'max:10'],
            'phone'                    => ['nullable', 'string', 'max:30'],
            'email'                    => ['nullable', 'email', 'max:191', 'unique:patients,email'],
            'address'                  => ['nullable', 'string'],
            'marital_status'           => ['nullable', 'in:single,married,divorced,widowed'],
            'allergies'                => ['nullable', 'string'],
            'chronic_disease'          => ['nullable', 'string'],
            'emergency_contact_name'   => ['nullable', 'string', 'max:191'],
            'emergency_contact_phone'  => ['nullable', 'string', 'max:30'],
            'registration_date'        => ['sometimes', 'date'],
            'photo'                    => ['nullable', 'image', 'max:2048'],
            'status'                   => ['sometimes', 'in:active,inactive,deceased'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required'  => 'Last name is required.',
            'gender.required'     => 'Gender is required.',
            'dob.required'        => 'Date of birth is required.',
            'dob.before'          => 'Date of birth must be in the past.',
            'email.unique'        => 'This email is already registered to another patient.',
        ];
    }
}
