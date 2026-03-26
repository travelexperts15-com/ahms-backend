<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'     => ['required', 'exists:patients,id'],
            'doctor_id'      => ['required', 'exists:doctors,id'],
            'opd_visit_id'   => ['nullable', 'exists:opd_visits,id'],
            'admission_id'   => ['nullable', 'exists:admissions,id'],
            'ordered_date'   => ['required', 'date'],
            'clinical_notes' => ['nullable', 'string'],
            'test_ids'       => ['required', 'array', 'min:1'],
            'test_ids.*'     => ['required', 'exists:lab_tests,id'],
        ];
    }
}
