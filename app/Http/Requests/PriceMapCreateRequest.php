<?php

namespace App\Http\Requests;

use Domain\Currency\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PriceMapCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['uuid'],
            'name' => ['required', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'currency' => ['required', 'string', new Enum(Currency::class)],
            'is_net' => ['required', 'boolean'],
        ];
    }
}
