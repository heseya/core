<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasswordResetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'redirect_url' => ['required', 'url'],
        ];
    }
}
