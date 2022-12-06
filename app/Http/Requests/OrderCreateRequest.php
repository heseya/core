<?php

namespace App\Http\Requests;

use App\Rules\ShippingPlaceValidation;
use App\Traits\MetadataRules;
use Illuminate\Validation\Rule;

class OrderCreateRequest extends OrderItemsRequest
{
    use MetadataRules;

    public function rules(): array
    {
        return array_merge(
            parent::rules(),
            $this->metadataRules(),
            [
                'email' => ['required', 'email', 'max:255'],
                'comment' => ['nullable', 'string', 'max:1000'],
                'shipping_method_id' => ['nullable', 'uuid'],
                'digital_shipping_method_id' => ['nullable', 'uuid'],

                'billing_address.name' => ['required', 'string', 'max:255'],
                'billing_address.phone' => ['required', 'string', 'max:20'],
                'billing_address.address' => ['required', 'string', 'max:255'],
                'billing_address.zip' => ['required', 'string', 'max:16'],
                'billing_address.city' => ['required', 'string', 'max:255'],
                'billing_address.country' => ['required', 'string', 'size:2'],
                'billing_address.vat' => ['nullable', 'string', 'max:15'],

                'coupons' => ['nullable', 'array'],
                'coupons.*' => ['string', 'max:64', 'exists:discounts,code'],

                'sale_ids' => ['nullable'],
                'sale_ids.*' => [
                    'uuid',
                    Rule::exists('discounts', 'id')->where(function ($query) {
                        return $query->where('code', null);
                    }),
                ],

                'invoice_requested' => ['boolean'],
                'shipping_place' => ['nullable', new ShippingPlaceValidation()],
            ]
        );
    }
}
