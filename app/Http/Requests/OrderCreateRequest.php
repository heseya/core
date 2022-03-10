<?php

namespace App\Http\Requests;

use App\Rules\DiscountAvailable;

class OrderCreateRequest extends OrderItemsRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'email' => ['required', 'email', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'shipping_method_id' => ['required', 'uuid', 'exists:shipping_methods,id'],

            'delivery_address.name' => ['required', 'string', 'max:255'],
            'delivery_address.phone' => ['required', 'string', 'max:20'],
            'delivery_address.address' => ['required', 'string', 'max:255'],
            'delivery_address.zip' => ['required', 'string', 'max:16'],
            'delivery_address.city' => ['required', 'string', 'max:255'],
            'delivery_address.country' => ['required', 'string', 'size:2'],
            'delivery_address.vat' => ['nullable', 'string', 'max:15'],

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
        ];
    }
}
