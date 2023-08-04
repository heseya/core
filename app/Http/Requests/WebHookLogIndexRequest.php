<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class WebHookLogIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status_code' => ['nullable', 'numeric'],
            'web_hook_id' => ['nullable', 'string'],
            'event' => ['nullable', 'string', new Enum(EventType::class)],
            'successful' => ['boolean'],
        ];
    }
}
