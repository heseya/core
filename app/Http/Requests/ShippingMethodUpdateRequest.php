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
            'public' => 'boolean',
            'black_list' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'countries' => 'array',
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
            'price_ranges' => ['array', 'min:1', new ShippingMethodPriceRanges()],
            'price_ranges.*.start' => ['required', 'numeric', 'min:0', 'distinct'],
            'price_ranges.*.value' => ['required', 'numeric', 'min:0'],
        ];
    }
}
