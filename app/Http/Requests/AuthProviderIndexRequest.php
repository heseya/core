<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthProviderIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'active' => ['boolean'],
        ];
    }
}
