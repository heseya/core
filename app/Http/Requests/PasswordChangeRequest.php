<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordChangeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:255'],
            'password_new ' => ['required', 'string', 'max:255', Password::defaults()],
        ];
    }
}
