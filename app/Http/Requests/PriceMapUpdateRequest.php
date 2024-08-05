<?php

namespace App\Http\Requests;

use Domain\Currency\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PriceMapUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string'],
            'description' => ['nullable', 'string'],
            'currency' => ['string', new Enum(Currency::class)],
            'is_net' => ['boolean'],
        ];
    }
}
