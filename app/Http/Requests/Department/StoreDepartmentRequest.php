<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:191', 'unique:departments,name'],
            'code'               => ['required', 'string', 'max:20', 'unique:departments,code'],
            'description'        => ['nullable', 'string'],
            'head_of_department' => ['nullable', 'string', 'max:191'],
            'phone'              => ['nullable', 'string', 'max:30'],
            'email'              => ['nullable', 'email', 'max:191'],
            'location'           => ['nullable', 'string', 'max:191'],
            'status'             => ['sometimes', 'in:active,inactive'],
        ];
    }
}
