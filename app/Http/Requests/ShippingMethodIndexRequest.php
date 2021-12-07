<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingMethodIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'country' => ['string', 'size:2', 'exists:countries,code'],
            'cart_value' => ['numeric'],
        ];
    }
}
