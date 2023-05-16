<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use App\Rules\EventExist;
use App\Rules\HttpsRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class WebHookCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'secret' => ['nullable', 'string', 'max:255'],
            'events' => ['bail', 'required', 'array', new EventExist()],
            'with_issuer' => ['required', 'boolean'],
            'with_hidden' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('secret', ['required'], function ($input) {
            foreach ($input->events ?? [] as $event) {
                if (in_array($event, EventType::$securedEvents)) {
                    return true;
                }
            }
            return false;
        });

        $validator->sometimes('url', [new HttpsRule()], function ($input) {
            foreach ($input->events ?? [] as $event) {
                if (in_array($event, EventType::$securedEvents)) {
                    return true;
                }
            }
            return false;
        });
    }
}
