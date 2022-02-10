<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LanguageCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'iso' => [
                'required',
                'string',
                'max:16',
                'unique:languages',
            ],
            'name' => [
                'required',
                'string',
                'max:80',
            ],
            'default' => [
                'required',
                'boolean',
            ],
            'hidden' => [
                'required',
                'boolean',
            ],
        ];
    }
}
