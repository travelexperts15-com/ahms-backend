<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Authorization is handled by route middleware (permission:users.create).
     * FormRequest::authorize() is not the right place to enforce roles
     * because it returns 403 with no JSON body on failure.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:191'],
            'email'             => ['required', 'email', 'max:191', 'unique:users,email'],
            'password'          => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'password_confirmation' => ['required'],
            'phone'             => ['nullable', 'string', 'max:30'],
            'gender'            => ['nullable', 'in:male,female,other'],
            'role'              => ['required', 'string', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Full name is required.',
            'email.required'    => 'Email address is required.',
            'email.unique'      => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed'=> 'Password confirmation does not match.',
            'role.required'     => 'A role must be assigned to the new user.',
            'role.exists'       => 'The selected role does not exist.',
        ];
    }
}
