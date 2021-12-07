<?php

namespace App\Http\Requests;

use App\Rules\EventExist;
use Illuminate\Foundation\Http\FormRequest;

class WebHookUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:255'],
            'secret' => ['nullable', 'string', 'max:255'],
            'events' => ['bail', 'nullable', 'array', new EventExist()],
            'with_issuer' => ['nullable'],
            'with_hidden' => ['nullable'],
        ];
    }
}
