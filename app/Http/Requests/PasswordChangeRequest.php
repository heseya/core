<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasswordChangeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:255'],
            'password_new' => ['required', 'string', 'max:255', 'min:10'],
        ];
    }
}
