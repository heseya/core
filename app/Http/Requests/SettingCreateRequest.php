<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:settings',
                Rule::notIn(array_keys(config('settings'))),
            ],
            'value' => ['required', 'string', 'max:255'],
            'public' => ['required', 'boolean'],
        ];
    }
}
