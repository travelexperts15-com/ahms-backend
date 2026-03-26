<?php

namespace App\Http\Requests\Ipd;

use Illuminate\Foundation\Http\FormRequest;

class DischargePatientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'discharge_date'       => ['required', 'date'],
            'discharge_time'       => ['required', 'date_format:H:i'],
            'diagnosis'            => ['nullable', 'string'],
            'treatment_summary'    => ['nullable', 'string'],
            'discharge_notes'      => ['nullable', 'string'],
            'discharge_condition'  => ['required', 'in:recovered,improved,unchanged,deteriorated,deceased'],
        ];
    }
}
