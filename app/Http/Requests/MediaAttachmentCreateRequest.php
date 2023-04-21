<?php

namespace App\Http\Requests;

use App\Enums\MediaAttachmentType;
use App\Enums\VisibilityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MediaAttachmentCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'media_id' => ['required', 'uuid'],
            'name' => ['required', 'string'],
            'type' => ['required', new Enum(MediaAttachmentType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', new Enum(VisibilityType::class)],
        ];
    }
}
