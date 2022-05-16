<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'shipping_number' => ['nullable', 'string'],

            'delivery_address' => ['nullable', 'array'],
            'delivery_address.name' => ['string', 'max:255'],
            'delivery_address.phone' => ['string', 'max:20'],
            'delivery_address.address' => ['string', 'max:255'],
            'delivery_address.zip' => ['string', 'max:16'],
            'delivery_address.city' => ['string', 'max:255'],
            'delivery_address.country' => ['string', 'size:2'],
            'delivery_address.vat' => ['nullable', 'string', 'max:15'],

            'invoice_address' => ['nullable', 'array'],
            'invoice_address.name' => ['string', 'max:255'],
            'invoice_address.phone' => ['string', 'max:20'],
            'invoice_address.address' => ['string', 'max:255'],
            'invoice_address.zip' => ['string', 'max:16'],
            'invoice_address.city' => ['string', 'max:255'],
            'invoice_address.country' => ['string', 'size:2'],
            'invoice_address.vat' => ['nullable', 'string', 'max:15'],

            'validation' => ['boolean'],
        ];
    }
}
