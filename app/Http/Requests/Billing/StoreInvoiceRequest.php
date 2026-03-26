<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'    => ['required', 'exists:patients,id'],
            'admission_id'  => ['nullable', 'exists:admissions,id'],
            'opd_visit_id'  => ['nullable', 'exists:opd_visits,id'],
            'invoice_date'  => ['required', 'date'],
            'due_date'      => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'discount'      => ['nullable', 'numeric', 'min:0'],
            'tax'           => ['nullable', 'numeric', 'min:0'],
            'notes'         => ['nullable', 'string'],
            'items'         => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.category'    => ['sometimes', 'in:service,medicine,lab,bed,procedure'],
            'items.*.quantity'    => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'items.*.discount'    => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
