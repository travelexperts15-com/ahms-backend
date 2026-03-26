<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'       => ['required', 'exists:patients,id'],
            'doctor_id'        => ['required', 'exists:doctors,id'],
            'department_id'    => ['nullable', 'exists:departments,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'type'             => ['sometimes', 'in:opd,followup,emergency,consultation'],
            'status'           => ['sometimes', 'in:scheduled,confirmed,completed,cancelled,no_show'],
            'symptoms'         => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required'       => 'Patient is required.',
            'patient_id.exists'         => 'Selected patient does not exist.',
            'doctor_id.required'        => 'Doctor is required.',
            'doctor_id.exists'          => 'Selected doctor does not exist.',
            'appointment_date.required' => 'Appointment date is required.',
            'appointment_date.after_or_equal' => 'Appointment date cannot be in the past.',
            'appointment_time.required' => 'Appointment time is required.',
        ];
    }
}
