<?php

namespace App\Http\Requests;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Rules\Boolean;
use Illuminate\Validation\Rules\Enum;

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
            'type' => [new Enum(DiscountType::class)],
            'priority' => ['integer'],
            'target_type' => [new Enum(DiscountTargetType::class)],
            'target_is_allow_list' => [new Boolean()],
        ]);
    }
}
