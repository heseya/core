<?php

namespace App\Http\Requests;

use App\Rules\ShippingAddressRequired;
use App\Rules\ShippingPlaceValidation;
use Illuminate\Foundation\Http\FormRequest;

class OrderUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email'],
            'comment' => ['nullable', 'string', 'max:1000'],

            'shipping_address' => ['nullable', 'array', new ShippingAddressRequired()],
            'shipping_address.name' => ['required_with_all:shipping_address', 'string', 'max:255'],
            'shipping_address.phone' => ['required_with_all:shipping_address', 'string', 'max:20'],
            'shipping_address.address' => ['required_with_all:shipping_address', 'string', 'max:255'],
            'shipping_address.zip' => ['required_with_all:shipping_address', 'string', 'max:16'],
            'shipping_address.city' => ['required_with_all:shipping_address', 'string', 'max:255'],
            'shipping_address.country' => ['required_with_all:shipping_address', 'string', 'size:2'],
            'shipping_address.vat' => ['nullable', 'string', 'max:15'],

            'billing_address' => ['nullable', 'array'],
            'billing_address.name' => ['required_with_all:billing_address', 'string', 'max:255'],
            'billing_address.phone' => ['required_with_all:billing_address', 'string', 'max:20'],
            'billing_address.address' => ['required_with_all:billing_address', 'string', 'max:255'],
            'billing_address.zip' => ['required_with_all:billing_address', 'string', 'max:16'],
            'billing_address.city' => ['required_with_all:billing_address', 'string', 'max:255'],
            'billing_address.country' => ['required_with_all:billing_address', 'string', 'size:2'],
            'billing_address.vat' => ['nullable', 'string', 'max:15'],

            'validation' => ['boolean'],
            'invoice_requested' => ['boolean'],
            'shipping_place' => ['nullable', new ShippingPlaceValidation()],

        ];
    }
}
