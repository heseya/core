<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'icon' => ['string', 'max:255'],
            'public' => [new Boolean()],
            'url' => ['string', 'url'],
        ];
    }
}
