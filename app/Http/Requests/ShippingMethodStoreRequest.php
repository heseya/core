<?php

namespace App\Http\Requests;

use App\Enums\ShippingType;
use App\Rules\Price;
use App\Rules\ShippingMethodPriceRanges;
use App\Traits\MetadataRules;
use BenSampo\Enum\Rules\EnumValue;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;

class ShippingMethodStoreRequest extends FormRequest
{
    use MetadataRules;

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'name' => ['required', 'string', 'max:255'],
                'public' => ['boolean'],
                'block_list' => ['boolean'],
                'payment_methods' => 'array',
                'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
                'countries' => 'array',
                'countries.*' => ['string', 'size:2', 'exists:countries,code'],
                'price_ranges' => ['required', new ShippingMethodPriceRanges()],
                'price_ranges.*' => [new Price(['value', 'start'], min: BigDecimal::zero())],
                'shipping_time_min' => ['required', 'numeric', 'integer', 'min:0'],
                'shipping_time_max' => ['required', 'numeric', 'integer', 'min:0', 'gte:shipping_time_min'],
                'integration_key' => ['string'],
                'shipping_type' => ['required', new EnumValue(ShippingType::class, false)],
                'shipping_points' => ['array'],
                'shipping_points.*.id' => ['string', 'exists:addresses,id'],
            ]
        );
    }
}
