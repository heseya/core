<?php

namespace App\Http\Requests;

use App\Models\Discount;
use Illuminate\Validation\Rule;

class DiscountUpdateRequest extends DiscountCreateRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = parent::rules();

        /** @var Discount $discount */
        $discount = $this->route('discount');

        $rules['code'] = [
            'required',
            'string',
            'max:64',
            Rule::unique('discounts')->ignore($discount->code, 'code'),
        ];

        return $rules;
    }
}
