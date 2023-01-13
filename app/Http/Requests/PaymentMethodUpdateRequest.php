<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'icon' => ['string', 'max:255'],
            'public' => ['boolean'],
            'url' => ['string', 'url'],
        ];
    }
}
