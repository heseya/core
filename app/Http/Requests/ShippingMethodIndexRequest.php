<?php

namespace App\Http\Requests;

use App\Rules\Price;
use Illuminate\Foundation\Http\FormRequest;

class ShippingMethodIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'country' => ['string', 'size:2', 'exists:countries,code'],
            'cart_value' => [new Price(['value'])],
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
        ];
    }
}
