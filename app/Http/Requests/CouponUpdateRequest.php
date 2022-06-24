<?php

namespace App\Http\Requests;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\Discount;
use App\Rules\Boolean;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CouponUpdateRequest extends CouponCreateRequest
{
    public function rules(): array
    {
        /** @var Discount $coupon */
        $coupon = $this->route('coupon');

        return array_merge(parent::rules(), [
            'name' => ['filled', 'string', 'max:255'],
            'value' => ['numeric'],
            'type' => [new Enum(DiscountType::class)],
            'priority' => ['integer'],
            'target_type' => [new Enum(DiscountTargetType::class)],
            'target_is_allow_list' => [new Boolean()],
            'code' => [
                'alpha_dash',
                'max:64',
                Rule::unique('discounts')->ignore($coupon->code, 'code'),
            ],
        ]);
    }
}
