<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'iso' => [
                'string',
                'max:16',
                Rule::unique('languages')->ignore($this->input('iso'), 'sku'),
            ],
            'name' => ['string', 'max:80'],
            'default' => ['boolean'],
            'hidden' => ['boolean'],
        ];
    }
}
