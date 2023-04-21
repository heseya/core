<?php

namespace App\Http\Requests;

use App\Enums\MediaAttachmentType;
use App\Enums\VisibilityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MediaAttachmentUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string'],
            'type' => [new Enum(MediaAttachmentType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => [new Enum(VisibilityType::class)],
        ];
    }
}
