<?php

namespace App\Http\Requests;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Rules\Translations;
use Illuminate\Validation\Rules\Enum;

class SaleUpdateRequest extends SaleCreateRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'translations' => ['sometimes', new Translations(['name', 'description_html', 'description'])],
            'value' => ['numeric', 'gte:0'],
            'type' => [new Enum(DiscountType::class)],
            'priority' => ['integer'],
            'target_type' => [new Enum(DiscountTargetType::class)],
            'target_is_allow_list' => ['boolean'],
            'active' => ['boolean'],
        ]);
    }
}
