<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use App\Rules\Boolean;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class MediaIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', new EnumValue(MediaType::class, false), 'max:255'],
            'has_relationships' => [new Boolean()],
            'ids' => ['array'],
            'ids.*' => ['uuid'],
        ];
    }
}
