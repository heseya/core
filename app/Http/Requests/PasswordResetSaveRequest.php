<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordResetSaveRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:255', Password::defaults()],
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }
}
