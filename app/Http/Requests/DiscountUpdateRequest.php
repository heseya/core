<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class DiscountUpdateRequest extends DiscountCreateRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['code'] = [
            'required',
            'string',
            'max:64',
            Rule::unique('discounts')->ignore($this->route('discount')->code, 'code'),
        ];

        return $rules;
    }
}
