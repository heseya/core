<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebHookIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status_code' => ['nullable', 'numeric'],
            'web_hook_id' => ['nullable', 'string'],
            'event' => ['nullable', 'string'],
        ];
    }
}
