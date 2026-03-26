<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'        => ['required', 'exists:users,id'],
            'month'          => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'basic_salary'   => ['required', 'numeric', 'min:0'],
            'allowances'     => ['nullable', 'numeric', 'min:0'],
            'overtime_pay'   => ['nullable', 'numeric', 'min:0'],
            'deductions'     => ['nullable', 'numeric', 'min:0'],
            'tax'            => ['nullable', 'numeric', 'min:0'],
            'payment_date'   => ['nullable', 'date'],
            'payment_method' => ['sometimes', 'in:cash,bank_transfer,mobile_money'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
