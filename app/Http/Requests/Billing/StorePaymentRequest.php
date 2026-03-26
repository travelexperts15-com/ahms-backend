<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_method'   => ['required', 'in:cash,card,bank_transfer,mobile_money,insurance'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_date'     => ['required', 'date'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
