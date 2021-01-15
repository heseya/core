<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProductUpdateRequest extends ProductCreateRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return parent::rules() + [
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('products')->ignore($this->product->slug, 'slug'),
            ],
        ];
    }
}
