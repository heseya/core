<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProductUpdateRequest extends ProductCreateRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['slug'] = [
            'required',
            'string',
            'max:255',
            'alpha_dash',
            Rule::unique('products')->ignore($this->route('product')->slug, 'slug'),
        ];

        $rules['published'] = ['nullable', 'array', 'min:1'];

        return $rules;
    }
}
