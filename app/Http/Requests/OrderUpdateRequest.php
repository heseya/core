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

            'shipping_address' => ['nullable', 'array'],
            'shipping_address.name' => ['required_with_all:shipping_address', 'string', 'max:255'],
            'shipping_address.phone' => ['required_with_all:shipping_address', 'string', 'max:20'],
            'shipping_address.address' => ['required_with_all:shipping_address', 'string', 'max:255'],
            'shipping_address.zip' => ['required_with_all:shipping_address', 'string', 'max:16'],
            'shipping_address.city' => ['required_with_all:shipping_address', 'string', 'max:255'],
            'shipping_address.country' => ['required_with_all:shipping_address', 'string', 'size:2'],
            'shipping_address.vat' => ['nullable', 'string', 'max:15'],

            'invoice_address' => ['nullable', 'array'],
            'invoice_address.name' => ['required_with_all:invoice_address', 'string', 'max:255'],
            'invoice_address.phone' => ['required_with_all:invoice_address', 'string', 'max:20'],
            'invoice_address.address' => ['required_with_all:invoice_address', 'string', 'max:255'],
            'invoice_address.zip' => ['required_with_all:invoice_address', 'string', 'max:16'],
            'invoice_address.city' => ['required_with_all:invoice_address', 'string', 'max:255'],
            'invoice_address.country' => ['required_with_all:invoice_address', 'string', 'size:2'],
            'invoice_address.vat' => ['nullable', 'string', 'max:15'],

            'validation' => ['boolean'],
        ];
    }
}
