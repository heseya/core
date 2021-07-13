<?php

namespace App\Http\Requests;

class PasswordResetSaveRequest extends PasswordChangeRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }
}
