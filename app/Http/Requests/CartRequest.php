<?php

namespace App\Http\Requests;

use App\Rules\ProductPublic;
use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sales_channel_id' => ['required', 'uuid'],

            'shipping_method_id' => ['nullable', 'uuid'],
            'digital_shipping_method_id' => ['nullable', 'uuid'],

            'coupons' => ['nullable', 'array'],
            'coupons.*' => ['string', 'max:64'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.cartitem_id' => ['required', 'string'],
            'items.*.product_id' => ['required', 'uuid', new ProductPublic()],
            'items.*.quantity' => ['required', 'integer', 'min:1'],

            'items.*.schemas' => ['nullable', 'array'],
        ];
    }
}
