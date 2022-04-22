<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => ['string', 'max:255'],
            'alias' => ['string', 'max:255'],
            'icon' => ['string', 'max:255'],
            'public' => ['boolean'],
            'url' => ['string', 'url'],
        ];
    }
}
