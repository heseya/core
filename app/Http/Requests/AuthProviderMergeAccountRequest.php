<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthProviderMergeAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'merge_token' => ['required', 'string'],
        ];
    }
}
