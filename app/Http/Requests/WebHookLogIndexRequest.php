<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class WebHookLogIndexRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'successful',
    ];

    public function rules(): array
    {
        return [
            'status_code' => ['nullable', 'numeric'],
            'web_hook_id' => ['nullable', 'string'],
            'event' => ['nullable', 'string', new Enum(EventType::class)],
            'successful' => [new Boolean()],
        ];
    }
}
