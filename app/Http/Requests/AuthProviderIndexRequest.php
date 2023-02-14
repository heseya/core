<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use Illuminate\Foundation\Http\FormRequest;

class AuthProviderIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'active' => [new Boolean()],
        ];
    }
}
