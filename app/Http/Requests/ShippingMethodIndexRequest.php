<?php

namespace App\Http\Requests;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;

class ShippingMethodIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'country' => ['string', 'size:2', 'exists:countries,code'],
            'cart_value' => [new Money()],
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
        ];
    }
}
