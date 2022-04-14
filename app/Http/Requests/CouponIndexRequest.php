<?php

namespace App\Http\Requests;

class CouponIndexRequest extends SaleIndexRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['code'] = ['string', 'max:64'];

        return $rules;
    }
}
