<?php

namespace App\Http\Requests;

use App\Rules\EventExist;
use Illuminate\Foundation\Http\FormRequest;

class WebHookCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'secret' => ['nullable', 'string', 'max:255'],
            'events' => ['bail', 'required', 'array', new EventExist()],
            'with_issuer' => ['required'],
            'with_hidden' => ['required'],
        ];
    }
}
