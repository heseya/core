<?php

namespace App\Http\Requests;

use App\Rules\ProductPublic;
use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipping_method_id' => ['nullable', 'uuid', 'exists:shipping_methods,id'],

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
