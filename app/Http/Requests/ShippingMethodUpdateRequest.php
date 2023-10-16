<?php

namespace App\Http\Requests;

use App\Rules\ShippingMethodPriceRanges;
use Illuminate\Foundation\Http\FormRequest;

class ShippingMethodUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'public' => ['boolean'],
            'block_list' => ['boolean'],
            'payment_on_delivery' => ['sometimes', 'boolean'],
            'payment_methods' => ['array', 'prohibited_if:payment_on_delivery,true'],
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'countries' => 'array',
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
            'price_ranges' => ['array', 'min:1', new ShippingMethodPriceRanges()],
            'price_ranges.*.start' => ['numeric', 'min:0', 'distinct'],
            'price_ranges.*.value' => ['numeric', 'min:0'],
            'shipping_time_min' => ['numeric', 'integer', 'min:0'],
            'shipping_time_max' => ['numeric', 'integer', 'min:0', 'gte:shipping_time_min'],
        ];
    }
}
