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

                'sales_channel_id' => ['required', 'uuid'],

                'digital_shipping_method_id' => ['nullable', 'uuid'],
                'shipping_method_id' => ['nullable', 'uuid'],
                'shipping_place' => ['nullable', 'required_with:shipping_method_id', new ShippingPlaceValidation()],

                'billing_address.name' => ['required', 'string', 'max:255'],
                'billing_address.phone' => ['required', 'string', 'max:20'],
                'billing_address.address' => ['required', 'string', 'max:255'],
                'billing_address.zip' => ['required', 'string', 'max:16'],
                'billing_address.city' => ['required', 'string', 'max:255'],
                'billing_address.country' => ['required', 'string', 'size:2'],
                'billing_address.vat' => ['nullable', 'string', 'max:15'],

                'coupons' => ['nullable', 'array'],
                'coupons.*' => [
                    'string',
                    'max:64',
                    Rule::exists('discounts', 'code')->where(fn ($query) => $query->where('active', true)),
                ],

                'sale_ids' => ['nullable'],
                'sale_ids.*' => [
                    'uuid',
                    Rule::exists('discounts', 'id')->where(fn ($query) => $query->where('code', null)->where('active', true)),
                ],

                'invoice_requested' => ['boolean'],
            ]
        );
    }
}
