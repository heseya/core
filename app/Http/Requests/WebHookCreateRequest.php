<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Rules\EventExist;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class WebHookCreateRequest extends FormRequest
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
            'url' => ['required', 'url', 'max:255'],
            'secret' => ['nullable', 'string', 'max:255'],
            'events' => ['bail', 'required', 'array', new EventExist()],
            'with_issuer' => ['required', new Boolean()],
            'with_hidden' => ['required', new Boolean()],
        ];
    }
}
