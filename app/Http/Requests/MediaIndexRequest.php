<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MediaIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', new Enum(MediaType::class), 'max:255'],
            'has_relationships' => ['boolean'],
            'ids' => ['array'],
            'ids.*' => ['uuid'],
        ];
    }
}
