<?php

namespace App\Http\Requests;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use BenSampo\Enum\Rules\EnumValue;

class SaleUpdateRequest extends SaleCreateRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['filled', 'string', 'max:255'],
            'value' => ['numeric'],
            'type' => [new EnumValue(DiscountType::class, false)],
            'priority' => ['integer'],
            'target_type' => [new EnumValue(DiscountTargetType::class, false)],
            'target_is_allow_list' => ['boolean'],
            'active' => ['boolean'],
        ]);
    }
}
