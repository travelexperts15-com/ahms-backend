<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public route — no auth check needed
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please provide a valid email address.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 6 characters.',
        ];
    }
}
