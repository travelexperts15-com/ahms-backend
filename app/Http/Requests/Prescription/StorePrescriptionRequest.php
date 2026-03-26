<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'      => ['required', 'exists:patients,id'],
            'doctor_id'       => ['required', 'exists:doctors,id'],
            'opd_visit_id'    => ['nullable', 'exists:opd_visits,id'],
            'admission_id'    => ['nullable', 'exists:admissions,id'],
            'prescribed_date' => ['required', 'date'],
            'notes'           => ['nullable', 'string'],
            'items'           => ['required', 'array', 'min:1'],
            'items.*.medicine_name' => ['required', 'string', 'max:191'],
            'items.*.dosage'        => ['nullable', 'string', 'max:100'],
            'items.*.frequency'     => ['nullable', 'string', 'max:100'],
            'items.*.duration'      => ['nullable', 'string', 'max:100'],
            'items.*.route'         => ['nullable', 'string', 'max:50'],
            'items.*.instructions'  => ['nullable', 'string'],
            'items.*.quantity'      => ['nullable', 'integer', 'min:1'],
        ];
    }
}
