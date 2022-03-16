<?php

namespace App\Http\Requests;

use App\Rules\DiscountAvailable;
use App\Rules\ShippingAddressRequired;
use App\Rules\ShippingPlaceValidation;

class OrderCreateRequest extends OrderItemsRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'email' => ['required', 'email', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'shipping_method_id' => ['required', 'uuid', 'exists:shipping_methods,id'],

            'shipping_address' => ['nullable', 'array', new ShippingAddressRequired()],
            'shipping_address.name' => ['required_with_all:shipping_address', 'string', 'max:255'],
            'shipping_address.phone' => ['required_with_all:shipping_address', 'string', 'max:20'],
            'shipping_address.address' => ['required_with_all:shipping_address', 'string', 'max:255'],
            'shipping_address.zip' => ['required_with_all:shipping_address', 'string', 'max:16'],
            'shipping_address.city' => ['required_with_all:shipping_address', 'string', 'max:255'],
            'shipping_address.country' => ['required_with_all:shipping_address', 'string', 'size:2'],
            'shipping_address.vat' => ['nullable', 'string', 'max:15'],

            'billing_address.name' => ['nullable', 'string', 'max:255'],
            'billing_address.phone' => ['nullable', 'string', 'max:20'],
            'billing_address.address' => ['nullable', 'string', 'max:255'],
            'billing_address.zip' => ['nullable', 'string', 'max:16'],
            'billing_address.city' => ['nullable', 'string', 'max:255'],
            'billing_address.country' => ['nullable', 'string', 'size:2'],
            'billing_address.vat' => ['nullable', 'string', 'max:15'],

            'discounts' => ['nullable', 'array'],
            'discounts.*' => ['string', 'max:64', 'exists:discounts,code', new DiscountAvailable()],

            'validation' => ['boolean'],
            'invoice_requested' => ['boolean'],
            'shipping_place' => ['nullable', new ShippingPlaceValidation()],
        ];
    }
}
