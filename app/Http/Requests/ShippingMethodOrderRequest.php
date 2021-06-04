<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingMethodOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'shipping_methods' => ['required', 'array'],
            'shipping_methods.*' => ['uuid', 'exists:shipping_methods,id'],
        ];
    }
}
