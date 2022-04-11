<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Rules\ShippingMethodPriceRanges;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class ShippingMethodStoreRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'public',
        'black_list',
    ];

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'public' => [new Boolean()],
            'black_list' => [new Boolean()],
            'payment_methods' => 'array',
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'countries' => 'array',
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
            'price_ranges' => ['required', 'array', 'min:1', new ShippingMethodPriceRanges()],
            'price_ranges.*.start' => ['required', 'numeric', 'min:0', 'distinct'],
            'price_ranges.*.value' => ['required', 'numeric', 'min:0'],
            'shipping_time_min' => ['required', 'numeric', 'integer', 'min:0'],
            'shipping_time_max' => ['required', 'numeric', 'integer', 'min:0', 'gte:shipping_time_min'],
        ];
    }
}
