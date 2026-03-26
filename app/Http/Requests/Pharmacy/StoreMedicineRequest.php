<?php

namespace App\Http\Requests\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:191'],
            'generic_name'   => ['nullable', 'string', 'max:191'],
            'category'       => ['nullable', 'string', 'max:100'],
            'type'           => ['nullable', 'string', 'max:50'],
            'strength'       => ['nullable', 'string', 'max:100'],
            'manufacturer'   => ['nullable', 'string', 'max:191'],
            'unit'           => ['nullable', 'string', 'max:50'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'reorder_level'  => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price'     => ['nullable', 'numeric', 'min:0'],
            'expiry_date'    => ['nullable', 'date'],
            'batch_number'   => ['nullable', 'string', 'max:100'],
            'status'         => ['sometimes', 'in:active,inactive,out_of_stock'],
        ];
    }
}
