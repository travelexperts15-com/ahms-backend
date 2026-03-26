<?php

namespace App\Http\Requests\Opd;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpdVisitRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'            => ['required', 'exists:patients,id'],
            'doctor_id'             => ['required', 'exists:doctors,id'],
            'department_id'         => ['nullable', 'exists:departments,id'],
            'appointment_id'        => ['nullable', 'exists:appointments,id'],
            'visit_date'            => ['required', 'date'],
            'visit_time'            => ['required', 'date_format:H:i'],
            'chief_complaint'       => ['nullable', 'string', 'max:500'],
            'history_of_illness'    => ['nullable', 'string'],
            'examination_findings'  => ['nullable', 'string'],
            'diagnosis'             => ['nullable', 'string'],
            'treatment_plan'        => ['nullable', 'string'],
            'notes'                 => ['nullable', 'string'],
            'blood_pressure'        => ['nullable', 'string', 'max:20'],
            'temperature'           => ['nullable', 'numeric', 'min:30', 'max:45'],
            'pulse_rate'            => ['nullable', 'integer', 'min:20', 'max:300'],
            'respiratory_rate'      => ['nullable', 'integer', 'min:5', 'max:60'],
            'weight'                => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'height'                => ['nullable', 'numeric', 'min:10', 'max:300'],
            'oxygen_saturation'     => ['nullable', 'integer', 'min:0', 'max:100'],
            'status'                => ['sometimes', 'in:in_progress,completed,referred'],
        ];
    }
}
