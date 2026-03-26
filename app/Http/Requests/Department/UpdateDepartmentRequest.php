<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('department')?->id;

        return [
            'name'               => ['sometimes', 'string', 'max:191', "unique:departments,name,{$id}"],
            'code'               => ['sometimes', 'string', 'max:20', "unique:departments,code,{$id}"],
            'description'        => ['nullable', 'string'],
            'head_of_department' => ['nullable', 'string', 'max:191'],
            'phone'              => ['nullable', 'string', 'max:30'],
            'email'              => ['nullable', 'email', 'max:191'],
            'location'           => ['nullable', 'string', 'max:191'],
            'status'             => ['sometimes', 'in:active,inactive'],
        ];
    }
}
