<?php

namespace App\Http\Requests;

use App\Enums\DiscountTargetType;
use App\Rules\Price;
use App\Rules\PricesEveryCurrency;
use App\Rules\Translations;
use Brick\Math\BigDecimal;
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

            'percentage' => ['prohibits:amounts', 'numeric', 'string', 'gte:0'],
            'amounts' => ['prohibits:percentage', new PricesEveryCurrency()],
            'amounts.*' => [new Price(['value'], min: BigDecimal::zero())],

            'priority' => ['integer'],
            'target_type' => [new Enum(DiscountTargetType::class)],
            'target_is_allow_list' => ['boolean'],
            'active' => ['boolean'],
        ]);
    }
}
