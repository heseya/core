<?php

namespace App\Http\Requests;

class CouponCreateRequest extends SaleCreateRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['code'] = ['required', 'alpha_dash', 'max:64', 'unique:discounts'];

        return $rules;
    }
}
