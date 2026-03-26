<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('staff')?->user_id;

        return [
            // User account fields (optional on update)
            'name'   => ['sometimes', 'string', 'max:191'],
            'email'  => ['sometimes', 'email', 'max:191', "unique:users,email,{$userId}"],
            'phone'  => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:male,female,other'],

            // Staff profile fields
            'first_name'              => ['sometimes', 'string', 'max:100'],
            'last_name'               => ['sometimes', 'string', 'max:100'],
            'department_id'           => ['nullable', 'exists:departments,id'],
            'dob'                     => ['nullable', 'date', 'before:today'],
            'address'                 => ['nullable', 'string'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:191'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'joining_date'            => ['nullable', 'date'],
            'position'                => ['nullable', 'string', 'max:191'],
            'basic_salary'            => ['nullable', 'numeric', 'min:0'],
            'bank_account'            => ['nullable', 'string', 'max:100'],
            'national_id'             => ['nullable', 'string', 'max:100'],
            'marital_status'          => ['nullable', 'in:single,married,divorced,widowed'],
            'employment_type'         => ['sometimes', 'in:full_time,part_time,contract,intern'],
        ];
    }
}
