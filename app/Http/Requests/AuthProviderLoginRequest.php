<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthProviderLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'return_url' => ['required', 'url'],
        ];
    }
}
