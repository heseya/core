<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class AttributeUpdateRequest extends AttributeStoreRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['slug'] = [
            'required',
            'string',
            'max:255',
            Rule::unique('attributes')->ignore($this->attribute, 'slug'),
        ];

        return $rules;
    }
}
