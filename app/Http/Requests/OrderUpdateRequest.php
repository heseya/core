<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Rules\ShippingPlaceValidation;
use Illuminate\Foundation\Http\FormRequest;

class OrderUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'shipping_number' => ['nullable', 'string'],
            'shipping_method_id' => ['nullable', 'uuid'],
            'digital_shipping_method_id' => ['nullable', 'uuid'],

            'billing_address' => ['nullable', 'array'],
            'billing_address.name' => ['string', 'max:255'],
            'billing_address.phone' => ['string', 'max:20'],
            'billing_address.address' => ['string', 'max:255'],
            'billing_address.zip' => ['string', 'max:16'],
            'billing_address.city' => ['string', 'max:255'],
            'billing_address.country' => ['string', 'size:2'],
            'billing_address.vat' => ['nullable', 'string', 'max:15'],

            'invoice_requested' => [new Boolean()],
            'shipping_place' => ['nullable', new ShippingPlaceValidation()],
        ];
    }
}
