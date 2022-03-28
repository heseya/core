<?php

namespace App\Http\Requests;

use App\Rules\DiscountAvailable;
use App\Rules\ShippingPlaceValidation;

class OrderCreateRequest extends OrderItemsRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'email' => ['required', 'email', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'shipping_method_id' => ['required', 'uuid', 'exists:shipping_methods,id'],

            'billing_address.name' => ['required', 'string', 'max:255'],
            'billing_address.phone' => ['required', 'string', 'max:20'],
            'billing_address.address' => ['required', 'string', 'max:255'],
            'billing_address.zip' => ['required', 'string', 'max:16'],
            'billing_address.city' => ['required', 'string', 'max:255'],
            'billing_address.country' => ['required', 'string', 'size:2'],
            'billing_address.vat' => ['nullable', 'string', 'max:15'],

            'discounts' => ['nullable', 'array'],
            'discounts.*' => ['string', 'max:64', 'exists:discounts,code', new DiscountAvailable()],

            'validation' => ['boolean'],
            'invoice_requested' => ['boolean'],
            'shipping_place' => ['nullable', new ShippingPlaceValidation()],
        ];
    }
}
