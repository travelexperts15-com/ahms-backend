<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabResultRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'results'                  => ['required', 'array', 'min:1'],
            'results.*.lab_test_id'    => ['required', 'exists:lab_tests,id'],
            'results.*.result_value'   => ['nullable', 'string', 'max:255'],
            'results.*.unit'           => ['nullable', 'string', 'max:50'],
            'results.*.normal_range'   => ['nullable', 'string', 'max:191'],
            'results.*.result_flag'    => ['nullable', 'in:normal,low,high,critical'],
            'results.*.remarks'        => ['nullable', 'string'],
        ];
    }
}
