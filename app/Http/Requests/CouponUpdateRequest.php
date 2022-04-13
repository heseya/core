<?php

namespace App\Http\Requests;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Rules\Boolean;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Validation\Rule;

class CouponUpdateRequest extends CouponCreateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['filled', 'string', 'max:255'],
            'value' => ['numeric'],
            'type' => [new EnumValue(DiscountType::class, false)],
            'priority' => ['integer'],
            'target_type' => [new EnumValue(DiscountTargetType::class, false)],
            'target_is_allow_list' => [new Boolean()],
            'code' => [
                'string',
                'max:64',
                Rule::unique('discounts')->ignore($this->route('coupon')->code, 'code'),
            ],
        ]);
    }
}
