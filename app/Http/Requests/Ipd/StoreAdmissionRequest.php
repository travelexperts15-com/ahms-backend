<?php

namespace App\Http\Requests\Ipd;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'           => ['required', 'exists:patients,id'],
            'doctor_id'            => ['required', 'exists:doctors,id'],
            'department_id'        => ['nullable', 'exists:departments,id'],
            'bed_id'               => ['nullable', 'exists:beds,id'],
            'admission_date'       => ['required', 'date'],
            'admission_time'       => ['required', 'date_format:H:i'],
            'admission_type'       => ['sometimes', 'in:regular,emergency,transfer'],
            'reason_for_admission' => ['nullable', 'string'],
            'diagnosis'            => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Patient is required.',
            'doctor_id.required'  => 'Attending doctor is required.',
        ];
    }
}
