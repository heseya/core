<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodIndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'shipping_method_id' => ['uuid', 'exists:shipping_methods,id'],
            'order_code' => ['string', 'exists:orders,code'],
        ];
    }
}
