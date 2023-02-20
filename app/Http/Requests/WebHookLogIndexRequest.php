<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class WebHookLogIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status_code' => ['nullable', 'numeric'],
            'web_hook_id' => ['nullable', 'string'],
            'event' => ['nullable', 'string', new EnumValue(EventType::class, false)],
            'successful' => ['boolean'],
        ];
    }
}
