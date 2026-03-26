<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('patient')?->id;

        return [
            'first_name'               => ['sometimes', 'string', 'max:100'],
            'last_name'                => ['sometimes', 'string', 'max:100'],
            'gender'                   => ['sometimes', 'in:male,female,other'],
            'dob'                      => ['sometimes', 'date', 'before:today'],
            'blood_group'              => ['nullable', 'string', 'max:10'],
            'phone'                    => ['nullable', 'string', 'max:30'],
            'email'                    => ['nullable', 'email', 'max:191', "unique:patients,email,{$id}"],
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
}
