<?php

namespace App\Http\Requests;

use App\Enums\ShippingType;
use App\Rules\Boolean;
use App\Rules\ShippingMethodPriceRanges;
use App\Traits\BooleanRules;
use App\Traits\MetadataRules;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class ShippingMethodStoreRequest extends FormRequest
{
    use BooleanRules;
    use MetadataRules;

    protected array $booleanFields = [
        'public',
        'block_list',
    ];

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'name' => ['required', 'string', 'max:255'],
                'public' => [new Boolean()],
                'block_list' => [new Boolean()],
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
                'shipping_type' => ['required', new EnumValue(ShippingType::class, false)],
                'shipping_points' => ['array'],
                'shipping_points.*.id' => ['string', 'exists:addresses,id'],
            ]
        );
    }
}
