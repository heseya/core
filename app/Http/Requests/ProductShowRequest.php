<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductShowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'with_translations' => ['sometimes', 'boolean'],
            'attribute_slug' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
