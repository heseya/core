<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Rules\EventExist;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class WebHookUpdateRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'with_issuer',
        'with_hidden',
    ];

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:255'],
            'secret' => ['nullable', 'string', 'max:255'],
            'events' => ['bail', 'nullable', 'array', new EventExist()],
            'with_issuer' => [new Boolean()],
            'with_hidden' => [new Boolean()],
        ];
    }
}
