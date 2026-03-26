<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'       => ['sometimes', 'exists:patients,id'],
            'doctor_id'        => ['sometimes', 'exists:doctors,id'],
            'department_id'    => ['nullable', 'exists:departments,id'],
            'appointment_date' => ['sometimes', 'date'],
            'appointment_time' => ['sometimes', 'date_format:H:i'],
            'type'             => ['sometimes', 'in:opd,followup,emergency,consultation'],
            'status'           => ['sometimes', 'in:scheduled,confirmed,completed,cancelled,no_show'],
            'symptoms'         => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
