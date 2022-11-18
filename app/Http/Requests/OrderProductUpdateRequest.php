<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class OrderProductUpdateRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'is_delivered',
    ];

    public function rules(): array
    {
        return [
            'is_delivered' => ['required', new Boolean()],
            'urls' => ['array'],
            'urls.*' => ['nullable', 'url'],
        ];
    }
}
