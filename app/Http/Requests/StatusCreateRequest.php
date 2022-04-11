<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class StatusCreateRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'cancel',
        'hidden',
        'no_notifications',
    ];

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'color' => ['required', 'string', 'size:6'],
            'cancel' => [new Boolean()],
            'description' => ['string', 'max:255', 'nullable'],
            'hidden' => [new Boolean()],
            'no_notifications' => [new Boolean()],
        ];
    }
}
