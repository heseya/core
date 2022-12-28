<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class OrderProductSearchRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'shipping_digital',
    ];

    public function rules(): array
    {
        return [
            'shipping_digital' => ['nullable', new Boolean()],
        ];
    }
}
