<?php

namespace App\Http\Requests\Bed;

use Illuminate\Foundation\Http\FormRequest;

class StoreBedRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'bed_number'     => ['required', 'string', 'max:20', 'unique:beds,bed_number'],
            'department_id'  => ['nullable', 'exists:departments,id'],
            'ward'           => ['nullable', 'string', 'max:100'],
            'room_number'    => ['nullable', 'string', 'max:20'],
            'type'           => ['sometimes', 'in:general,private,icu,emergency,maternity'],
            'status'         => ['sometimes', 'in:available,occupied,maintenance,reserved'],
            'charge_per_day' => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
