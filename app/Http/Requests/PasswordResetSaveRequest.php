<?php

namespace App\Http\Requests;

class PasswordResetSaveRequest extends PasswordChangeRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'token' => ['required', 'string', 'min:32', 'max:128', 'alpha_dash'],
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }
}
