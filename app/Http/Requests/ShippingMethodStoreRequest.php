<?php

namespace App\Http\Requests;

use App\Enums\ShippingType;
use App\Rules\ShippingMethodPriceRanges;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class ShippingMethodStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'public' => 'boolean',
            'block_list' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'countries' => 'array',
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
            'price_ranges' => ['required', 'array', 'min:1', new ShippingMethodPriceRanges()],
            'price_ranges.*.start' => ['required', 'numeric', 'min:0', 'distinct'],
            'price_ranges.*.value' => ['required', 'numeric', 'min:0'],
            'shipping_time_min' => ['required', 'numeric', 'integer', 'min:0'],
            'shipping_time_max' => ['required', 'numeric', 'integer', 'min:0', 'gte:shipping_time_min'],
            'integration_key' => ['string'],
            'shipping_type' => [new EnumValue(ShippingType::class, false)],
            'shipping_points' => ['array'],
            'shipping_points.*.id' => ['string', 'exists:addresses,id'],
        ];
    }
}
