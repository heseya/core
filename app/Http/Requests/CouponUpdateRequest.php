<?php

namespace App\Http\Requests;

use App\Models\Discount;
use Illuminate\Validation\Rule;

class CouponUpdateRequest extends SaleUpdateRequest
{
    public function rules(): array
    {
        /** @var Discount $coupon */
        $coupon = $this->route('coupon');

        return array_merge(parent::rules(), [
            'code' => [
                'alpha_dash',
                'max:64',
                Rule::unique('discounts')->ignore($coupon->code, 'code'),
            ],
        ]);
    }
}
